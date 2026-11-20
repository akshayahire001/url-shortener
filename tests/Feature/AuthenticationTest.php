<?php

namespace Tests\Feature;

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_users_can_log_in_and_log_out(): void
    {
        $user = $this->userWithRole(Role::SALES);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('short-urls.index'));

        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_login_requires_valid_credentials(): void
    {
        $this->userWithRole(Role::MANAGER);

        $this->from('/login')->post('/login', [
            'email' => 'missing@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_fields_are_validated(): void
    {
        $this->post('/login', [
            'email' => 'not-an-email',
            'password' => '',
        ])->assertSessionHasErrors(['email', 'password']);
    }
}
