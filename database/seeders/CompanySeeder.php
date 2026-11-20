<?php

namespace Database\Seeders;

use App\Contracts\CompanyServiceInterface;
use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        if (Company::query()->exists()) {
            return;
        }

        app(CompanyServiceInterface::class)->create('Sembark');
    }
}
