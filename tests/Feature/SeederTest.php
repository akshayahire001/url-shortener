<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_one_company_and_a_raw_sql_super_admin(): void
    {
        $this->seed();

        $this->assertDatabaseCount('companies', 1);
        $this->assertDatabaseHas('companies', ['name' => 'Sembark']);

        foreach (Role::ALL as $role) {
            $this->assertDatabaseHas('roles', ['name' => $role]);
        }

        $this->assertDatabaseHas('users', ['email' => 'superadmin@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'sales@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'manager@example.com']);

        $assignedRoles = User::query()->with('role')->get()->map(fn (User $user) => $user->role->name);

        $this->assertFalse($assignedRoles->contains(Role::ADMIN));
        $this->assertFalse($assignedRoles->contains(Role::MEMBER));
        $this->assertCount(1, User::query()->pluck('company_id')->unique());

        $this->post('/login', [
            'email' => 'superadmin@example.com',
            'password' => 'password',
        ])->assertRedirect(route('short-urls.index'));
    }
}
