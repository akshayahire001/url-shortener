<?php

namespace Tests\Feature;

use App\Contracts\CompanyServiceInterface;
use App\Support\AssignmentRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CompanyConstraintTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_service_cannot_have_multiple_companies(): void
    {
        $companies = app(CompanyServiceInterface::class);

        $companies->create('Sembark');

        try {
            $companies->create('Another Company');
            $this->fail('A second company was created.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                AssignmentRules::SINGLE_COMPANY,
                $exception->validator->errors()->first('name')
            );
        }

        $this->assertDatabaseCount('companies', 1);
    }
}
