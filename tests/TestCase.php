<?php

namespace Tests;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function userWithRole(string $role, ?Company $company = null): User
    {
        $company ??= Company::factory()->create();
        $roleModel = Role::query()->firstOrCreate(['name' => $role]);

        return User::factory()->create([
            'role_id' => $roleModel->id,
            'company_id' => $company->id,
        ]);
    }
}
