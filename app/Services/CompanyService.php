<?php

namespace App\Services;

use App\Contracts\CompanyServiceInterface;
use App\Models\Company;
use App\Support\AssignmentRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CompanyService implements CompanyServiceInterface
{
    public function create(string $name): Company
    {
        return DB::transaction(function () use ($name) {
            $validator = Validator::make(
                ['name' => trim($name)],
                ['name' => ['required', 'string', 'max:255']]
            );

            $validator->after(function ($validator) {
                if (Company::query()->exists()) {
                    $validator->errors()->add('name', AssignmentRules::SINGLE_COMPANY);
                }
            });

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            return Company::query()->create([
                'name' => trim($name),
            ]);
        });
    }
}
