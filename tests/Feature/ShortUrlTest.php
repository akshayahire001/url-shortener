<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Role;
use App\Models\ShortUrl;
use App\Support\AssignmentRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShortUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_member_cannot_create_short_urls(): void
    {
        foreach ([Role::ADMIN, Role::MEMBER] as $role) {
            $user = $this->userWithRole($role);

            $this->actingAs($user)
                ->post(route('short-urls.store'), [
                    'original_url' => 'https://blocked.example/page',
                ])
                ->assertRedirect(route('short-urls.index'))
                ->assertSessionHas('error', AssignmentRules::ADMIN_MEMBER_CREATE);
        }

        $this->assertDatabaseCount('short_urls', 0);
    }

    public function test_super_admin_cannot_create_short_urls(): void
    {
        $superAdmin = $this->userWithRole(Role::SUPER_ADMIN);

        $this->actingAs($superAdmin)
            ->post(route('short-urls.store'), [
                'original_url' => 'https://blocked.example/super',
            ])
            ->assertRedirect(route('short-urls.index'))
            ->assertSessionHas('error', AssignmentRules::SUPER_ADMIN_CREATE);

        $this->assertDatabaseCount('short_urls', 0);
    }

    public function test_sales_and_manager_can_create_short_urls(): void
    {
        foreach ([Role::SALES, Role::MANAGER] as $role) {
            $user = $this->userWithRole($role);

            $this->actingAs($user)
                ->post(route('short-urls.store'), [
                    'original_url' => 'https://allowed.example/'.$role,
                ])
                ->assertRedirect(route('short-urls.index'));

            $this->assertDatabaseHas('short_urls', [
                'original_url' => 'https://allowed.example/'.$role,
                'user_id' => $user->id,
                'company_id' => $user->company_id,
            ]);
        }
    }

    public function test_original_url_must_be_a_valid_url(): void
    {
        $sales = $this->userWithRole(Role::SALES);

        $this->actingAs($sales)
            ->from(route('short-urls.create'))
            ->post(route('short-urls.store'), [
                'original_url' => 'not-a-url',
            ])
            ->assertSessionHasErrors('original_url');

        $this->assertDatabaseCount('short_urls', 0);
    }

    public function test_guests_cannot_create_short_urls(): void
    {
        $this->post(route('short-urls.store'), [
            'original_url' => 'https://example.com',
        ])->assertRedirect(route('login'));
    }

    public function test_super_admin_cannot_see_short_urls_for_every_company(): void
    {
        $company = Company::factory()->create();
        $superAdmin = $this->userWithRole(Role::SUPER_ADMIN, $company);
        $sales = $this->userWithRole(Role::SALES, $company);

        ShortUrl::factory()->create([
            'company_id' => $company->id,
            'user_id' => $sales->id,
            'code' => 'hideme01',
            'original_url' => 'https://hidden.example/secret',
        ]);

        $this->actingAs($superAdmin)
            ->get(route('short-urls.index'))
            ->assertOk()
            ->assertSee(AssignmentRules::SUPER_ADMIN_LIST)
            ->assertDontSee('https://hidden.example/secret')
            ->assertDontSee('hideme01');
    }

    public function test_admin_only_sees_short_urls_not_created_in_their_own_company(): void
    {
        $ownCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->userWithRole(Role::ADMIN, $ownCompany);
        $otherUser = $this->userWithRole(Role::SALES, $otherCompany);

        ShortUrl::factory()->create([
            'company_id' => $ownCompany->id,
            'user_id' => $admin->id,
            'code' => 'owncode1',
            'original_url' => 'https://owned.example/hidden',
        ]);

        ShortUrl::factory()->create([
            'company_id' => $otherCompany->id,
            'user_id' => $otherUser->id,
            'code' => 'othercd1',
            'original_url' => 'https://outside.example/visible',
        ]);

        $this->actingAs($admin)
            ->get(route('short-urls.index'))
            ->assertOk()
            ->assertSee('https://outside.example/visible')
            ->assertSee('othercd1')
            ->assertDontSee('https://owned.example/hidden')
            ->assertDontSee('owncode1');
    }

    public function test_member_only_sees_short_urls_not_created_by_themselves(): void
    {
        $company = Company::factory()->create();
        $member = $this->userWithRole(Role::MEMBER, $company);
        $someoneElse = $this->userWithRole(Role::SALES, $company);

        ShortUrl::factory()->create([
            'company_id' => $company->id,
            'user_id' => $member->id,
            'code' => 'minecode',
            'original_url' => 'https://mine.example/hidden',
        ]);

        ShortUrl::factory()->create([
            'company_id' => $company->id,
            'user_id' => $someoneElse->id,
            'code' => 'theircde',
            'original_url' => 'https://theirs.example/visible',
        ]);

        $this->actingAs($member)
            ->get(route('short-urls.index'))
            ->assertOk()
            ->assertSee('https://theirs.example/visible')
            ->assertSee('theircde')
            ->assertDontSee('https://mine.example/hidden')
            ->assertDontSee('minecode');
    }

    public function test_short_urls_are_not_publicly_resolvable_and_do_not_redirect(): void
    {
        $shortUrl = ShortUrl::factory()->create([
            'original_url' => 'https://target.example/page',
            'code' => 'abc12345',
        ]);

        $response = $this->get('/s/'.$shortUrl->code);

        $response->assertNotFound();
        $this->assertFalse($response->isRedirect());
        $response->assertHeaderMissing('Location');
        $response->assertSee(AssignmentRules::NOT_PUBLIC);
        $response->assertDontSee('https://target.example/page');
    }
}
