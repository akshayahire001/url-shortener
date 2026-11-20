<?php

namespace Database\Seeders;

use App\Contracts\InvitationServiceInterface;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class CompanyUserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::query()->where('email', 'superadmin@example.com')->firstOrFail();
        $invitations = app(InvitationServiceInterface::class);

        $accounts = [
            ['sales@example.com', Role::SALES, 'Sales User'],
            ['manager@example.com', Role::MANAGER, 'Manager User'],
        ];

        foreach ($accounts as [$email, $role, $name]) {
            if (User::query()->where('email', $email)->exists()) {
                continue;
            }

            $invitation = $invitations->invite($superAdmin, $email, $role, false, null);
            $invitations->accept($invitation->token, $name, 'password');
        }
    }
}
