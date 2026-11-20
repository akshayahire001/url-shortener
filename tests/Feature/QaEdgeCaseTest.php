<?php

namespace Tests\Feature;

use App\Contracts\CompanyServiceInterface;
use App\Models\Company;
use App\Models\Invitation;
use App\Models\Role;
use App\Models\ShortUrl;
use App\Models\User;
use App\Support\AssignmentRules;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class QaEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_protected_routes_redirect_unauthenticated_guests_to_login(): void
    {
        $this->get(route('short-urls.index'))->assertRedirect(route('login'));
        $this->get(route('short-urls.create'))->assertRedirect(route('login'));
        $this->post(route('short-urls.store'), ['original_url' => 'https://example.com'])->assertRedirect(route('login'));
        $this->get(route('invitations.create'))->assertRedirect(route('login'));
        $this->post(route('invitations.store'), [])->assertRedirect(route('login'));
        $this->post(route('logout'))->assertRedirect(route('login'));
    }

    public function test_authenticated_users_are_redirected_away_from_login_and_home(): void
    {
        $sales = $this->userWithRole(Role::SALES);

        $this->actingAs($sales)->get(route('login'))->assertRedirect(route('short-urls.index'));
        $this->actingAs($sales)->get('/')->assertRedirect(route('short-urls.index'));
    }

    public function test_sales_and_manager_cannot_access_or_submit_invitations(): void
    {
        $this->seed(RoleSeeder::class);

        foreach ([Role::SALES, Role::MANAGER] as $role) {
            $user = $this->userWithRole($role);

            $this->actingAs($user)
                ->get(route('invitations.create'))
                ->assertForbidden();

            $this->actingAs($user)
                ->post(route('invitations.store'), [
                    'email' => 'someone@example.com',
                    'role' => Role::SALES,
                    'new_company' => '0',
                ])
                ->assertForbidden();
        }
    }

    public function test_database_seeder_is_idempotent_when_run_multiple_times(): void
    {
        $this->seed();
        $this->seed();

        $this->assertDatabaseCount('companies', 1);
        $this->assertDatabaseCount('roles', 5);
        $this->assertDatabaseCount('users', 3);
    }

    public function test_case_insensitive_email_normalization_on_login_and_invitations(): void
    {
        $this->seed(RoleSeeder::class);

        $company = Company::factory()->create();
        $superAdmin = $this->userWithRole(Role::SUPER_ADMIN, $company);

        $this->actingAs($superAdmin)
            ->post(route('invitations.store'), [
                'email' => '  CaseUser@Example.COM ',
                'role' => Role::MANAGER,
                'new_company' => '0',
            ])
            ->assertRedirect(route('invitations.create'));

        $this->assertDatabaseHas('invitations', [
            'email' => 'caseuser@example.com',
        ]);

        // Duplicate pending invitation with different casing is rejected
        $this->actingAs($superAdmin)
            ->from(route('invitations.create'))
            ->post(route('invitations.store'), [
                'email' => 'CASEUSER@example.com',
                'role' => Role::MANAGER,
                'new_company' => '0',
            ])
            ->assertSessionHasErrors('email');

        auth()->logout();

        $this->post(route('login'), [
            'email' => strtoupper($superAdmin->email),
            'password' => 'password',
        ])->assertRedirect(route('short-urls.index'));
    }

    public function test_invalid_or_already_used_invitation_tokens_are_rejected_safely(): void
    {
        $this->seed(RoleSeeder::class);

        $this->get(route('invitations.accept', 'nonexistenttoken123'))->assertNotFound();

        $this->from('/login')
            ->post(route('invitations.accept.store', 'nonexistenttoken123'), [
                'name' => 'Ghost',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertSessionHasErrors('token');

        $superAdmin = $this->userWithRole(Role::SUPER_ADMIN);

        $this->actingAs($superAdmin)->post(route('invitations.store'), [
            'email' => 'once@example.com',
            'role' => Role::SALES,
            'new_company' => '0',
        ]);

        $invitation = Invitation::query()->where('email', 'once@example.com')->firstOrFail();

        $this->post(route('invitations.accept.store', $invitation->token), [
            'name' => 'First Accept',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('short-urls.index'));

        // Viewing or posting the same token again fails cleanly
        $this->get(route('invitations.accept', $invitation->token))->assertNotFound();

        $this->post(route('invitations.accept.store', $invitation->token), [
            'name' => 'Replay Attempt',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('token');
    }

    public function test_invitation_acceptance_validates_password_confirmation_and_existing_emails(): void
    {
        $this->seed(RoleSeeder::class);

        $company = Company::factory()->create();
        $superAdmin = $this->userWithRole(Role::SUPER_ADMIN, $company);

        $this->actingAs($superAdmin)->post(route('invitations.store'), [
            'email' => 'race@example.com',
            'role' => Role::SALES,
            'new_company' => '0',
        ]);

        $invitation = Invitation::query()->where('email', 'race@example.com')->firstOrFail();

        // Password mismatch and min length
        $this->from(route('invitations.accept', $invitation->token))
            ->post(route('invitations.accept.store', $invitation->token), [
                'name' => '',
                'password' => 'short',
                'password_confirmation' => 'different',
            ])
            ->assertSessionHasErrors(['name', 'password']);

        // Simulate user already created with same email before token accepted
        User::factory()->create([
            'email' => 'race@example.com',
            'company_id' => $company->id,
            'role_id' => Role::query()->where('name', Role::SALES)->value('id'),
        ]);

        $this->from(route('invitations.accept', $invitation->token))
            ->post(route('invitations.accept.store', $invitation->token), [
                'name' => 'Duplicate Email',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_nonexistent_public_short_url_code_returns_404_without_redirect(): void
    {
        $response = $this->get('/s/unknown99');

        $response->assertNotFound();
        $this->assertFalse($response->isRedirect());
        $response->assertHeaderMissing('Location');
        $response->assertSee(AssignmentRules::NOT_PUBLIC);
    }

    public function test_company_service_rejects_blank_company_names(): void
    {
        $companies = app(CompanyServiceInterface::class);

        try {
            $companies->create('   ');
            $this->fail('Blank company name should fail validation.');
        } catch (ValidationException $exception) {
            $this->assertTrue($exception->validator->errors()->has('name'));
        }

        $this->assertDatabaseCount('companies', 0);
    }

    public function test_create_short_url_screen_displays_exact_blocked_messages_per_role(): void
    {
        $company = Company::factory()->create();

        $this->actingAs($this->userWithRole(Role::SUPER_ADMIN, $company))
            ->get(route('short-urls.create'))
            ->assertOk()
            ->assertSee(AssignmentRules::SUPER_ADMIN_CREATE);

        $this->actingAs($this->userWithRole(Role::ADMIN, $company))
            ->get(route('short-urls.create'))
            ->assertOk()
            ->assertSee(AssignmentRules::ADMIN_MEMBER_CREATE);

        $this->actingAs($this->userWithRole(Role::MEMBER, $company))
            ->get(route('short-urls.create'))
            ->assertOk()
            ->assertSee(AssignmentRules::ADMIN_MEMBER_CREATE);

        $this->actingAs($this->userWithRole(Role::SALES, $company))
            ->get(route('short-urls.create'))
            ->assertOk()
            ->assertSee('Original URL');
    }

    public function test_login_rate_limits_repeated_failed_attempts(): void
    {
        $user = $this->userWithRole(Role::SALES);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->from(route('login'))->post(route('login'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }

        $response = $this->from(route('login'))->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_short_url_rejects_non_http_and_https_schemes(): void
    {
        $sales = $this->userWithRole(Role::SALES);

        foreach (['javascript:alert(1)', 'ftp://files.example.com/archive.zip', 'file:///etc/passwd'] as $unsafeUrl) {
            $this->actingAs($sales)
                ->from(route('short-urls.create'))
                ->post(route('short-urls.store'), [
                    'original_url' => $unsafeUrl,
                ])
                ->assertSessionHasErrors('original_url');
        }

        $this->assertDatabaseCount('short_urls', 0);
    }

    public function test_accepted_invitation_persists_email_verified_at_and_public_endpoint_supports_json(): void
    {
        $this->seed(RoleSeeder::class);

        $superAdmin = $this->userWithRole(Role::SUPER_ADMIN);

        $this->actingAs($superAdmin)->post(route('invitations.store'), [
            'email' => 'verified@example.com',
            'role' => Role::MANAGER,
            'new_company' => '0',
        ]);

        $invitation = Invitation::query()->where('email', 'verified@example.com')->firstOrFail();

        $this->post(route('invitations.accept.store', $invitation->token), [
            'name' => 'Verified Manager',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('short-urls.index'));

        $createdUser = User::query()->where('email', 'verified@example.com')->firstOrFail();
        $this->assertNotNull($createdUser->email_verified_at);

        $this->getJson('/s/abc12345')
            ->assertNotFound()
            ->assertExactJson([
                'message' => AssignmentRules::NOT_PUBLIC,
            ]);
    }

    public function test_short_url_listing_executes_constant_number_of_queries_without_n_plus_one(): void
    {
        $company = Company::factory()->create();
        $sales = $this->userWithRole(Role::SALES, $company);
        $manager = $this->userWithRole(Role::MANAGER, $company);

        ShortUrl::factory()->count(5)->create([
            'company_id' => $company->id,
            'user_id' => $sales->id,
        ]);

        ShortUrl::factory()->count(5)->create([
            'company_id' => $company->id,
            'user_id' => $manager->id,
        ]);

        $sales->load('role');

        DB::enableQueryLog();
        DB::flushQueryLog();

        $this->actingAs($sales)
            ->get(route('short-urls.index'))
            ->assertOk();

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        // 1 query for short_urls + 1 eager-load for users + 1 eager-load for companies = 3 queries
        $this->assertLessThanOrEqual(4, $queryCount);
    }
}
