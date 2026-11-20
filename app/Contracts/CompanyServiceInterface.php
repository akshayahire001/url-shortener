<?php

namespace App\Contracts;

use App\Models\Company;

interface CompanyServiceInterface
{
    public function create(string $name): Company;
}
