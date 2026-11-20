<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $now = now()->format('Y-m-d H:i:s');
        $password = Hash::make('password');
        $email = 'superadmin@example.com';

        DB::statement(
            'INSERT INTO users (name, email, password, role_id, company_id, email_verified_at, created_at, updated_at)
             SELECT ?, ?, ?, roles.id, companies.id, ?, ?, ?
             FROM roles
             CROSS JOIN companies
             WHERE roles.name = ?
               AND NOT EXISTS (SELECT 1 FROM users WHERE email = ?)
             LIMIT 1',
            [
                'Super Admin',
                $email,
                $password,
                $now,
                $now,
                $now,
                Role::SUPER_ADMIN,
                $email,
            ]
        );

        $created = User::query()->where('email', $email)->exists();

        if (! $created) {
            throw new RuntimeException('Raw SQL SuperAdmin seed did not insert a user.');
        }
    }
}
