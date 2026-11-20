<?php

namespace App\Contracts;

use App\Models\Invitation;
use App\Models\User;

interface InvitationServiceInterface
{
    public function invite(
        User $actor,
        string $email,
        string $role,
        bool $newCompany,
        ?string $companyName
    ): Invitation;

    public function accept(string $token, string $name, string $password): User;
}
