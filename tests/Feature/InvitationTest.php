<?php

namespace Tests\Feature;

use App\Models\Invitation;
use App\Models\Role;
use App\Models\User;
use App\Support\AssignmentRules;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_super_admin_cannot_invite_an_admin_in_a_new_company(): void
    {
        $superAdmin = $this->userWithRole(Role::SUPER_ADMIN);

        $this->actingAs($superAdmin)
            ->from(route('invitations.create'))
            ->post(route('invitations.store'), [
                'email' => 'admin@example.com',
                'role' => Role::ADMIN,
                'new_company' => '1',
                'company_name' => 'Second Company',
            ])
            ->assertSessionHasErrors([
                'role' => AssignmentRules::SUPER_ADMIN_INVITE,
                'company_name' => AssignmentRules::SINGLE_COMPANY,
            ]);

        $this->assertDatabaseCount('companies', 1);
        $this->assertDatabaseCount('invitations', 0);
        $this->assertDatabaseMissing('users', ['email' => 'admin@example.com']);
    }

    public function test_admin_cannot_invite_admin_or_member_in_their_own_company(): void
    {
        $admin = $this->userWithRole(Role::ADMIN);

        foreach ([Role::ADMIN, Role::MEMBER] as $role) {
            $this->actingAs($admin)
                ->from(route('invitations.create'))
                ->post(route('invitations.store'), [
                    'email' => strtolower($role).'@example.com',
                    'role' => $role,
                    'new_company' => '0',
                ])
                ->assertSessionHasErrors([
                    'role' => AssignmentRules::ADMIN_INVITE,
                ]);
        }

        $this->assertDatabaseCount('invitations', 0);
        $this->assertDatabaseCount('companies', 1);
    }

    public function test_a_user_cannot_be_assigned_admin_or_member(): void
    {
        $superAdmin = $this->userWithRole(Role::SUPER_ADMIN);

        $this->actingAs($superAdmin)
            ->from(route('invitations.create'))
            ->post(route('invitations.store'), [
                'email' => 'member@example.com',
                'role' => Role::MEMBER,
                'new_company' => '0',
            ])
            ->assertSessionHasErrors([
                'role' => AssignmentRules::USER_ROLE,
            ]);

        $this->assertDatabaseCount('invitations', 0);
    }

    public function test_super_admin_can_invite_sales_into_the_existing_company(): void
    {
        $superAdmin = $this->userWithRole(Role::SUPER_ADMIN);

        $this->actingAs($superAdmin)
            ->post(route('invitations.store'), [
                'email' => 'new.sales@example.com',
                'role' => Role::SALES,
                'new_company' => '0',
            ])
            ->assertRedirect(route('invitations.create'))
            ->assertSessionHas('invite_url');

        $invitation = Invitation::query()->where('email', 'new.sales@example.com')->first();

        $this->assertNotNull($invitation);
        $this->assertSame($superAdmin->company_id, $invitation->company_id);
        $this->assertDatabaseCount('companies', 1);

        $this->post(route('invitations.accept.store', $invitation->token), [
            'name' => 'New Sales',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('short-urls.index'));

        $user = User::query()->where('email', 'new.sales@example.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole(Role::SALES));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($invitation->fresh()->accepted_at);
    }

    public function test_member_cannot_open_the_invitation_form(): void
    {
        $member = $this->userWithRole(Role::MEMBER);

        $this->actingAs($member)
            ->get(route('invitations.create'))
            ->assertForbidden();
    }
}
