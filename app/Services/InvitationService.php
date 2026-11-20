<?php

namespace App\Services;

use App\Contracts\InvitationServiceInterface;
use App\Models\Invitation;
use App\Models\Role;
use App\Models\User;
use App\Support\AssignmentRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InvitationService implements InvitationServiceInterface
{
    public function invite(
        User $actor,
        string $email,
        string $role,
        bool $newCompany,
        ?string $companyName
    ): Invitation {
        $normalizedEmail = Str::lower(trim($email));

        $validator = Validator::make(
            [
                'email' => $normalizedEmail,
                'role' => $role,
                'new_company' => $newCompany,
                'company_name' => $companyName,
            ],
            [
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    'unique:users,email',
                    Rule::unique('invitations', 'email')->whereNull('accepted_at'),
                ],
                'role' => ['required', 'string', Rule::in(Role::ALL)],
                'new_company' => ['required', 'boolean'],
                'company_name' => ['nullable', 'string', 'max:255'],
            ]
        );

        $validator->after(function ($validator) use ($actor, $role, $newCompany) {
            if (! $actor->canInvite()) {
                $validator->errors()->add('email', 'You are not allowed to invite users.');
            }

            // Assignment imposes negative invitation and role-assignment constraints
            if ($actor->hasRole(Role::SUPER_ADMIN) && $role === Role::ADMIN && $newCompany) {
                $validator->errors()->add('role', AssignmentRules::SUPER_ADMIN_INVITE);
            }

            if (
                $actor->hasRole(Role::ADMIN)
                && in_array($role, [Role::ADMIN, Role::MEMBER], true)
                && ! $newCompany
            ) {
                $validator->errors()->add('role', AssignmentRules::ADMIN_INVITE);
            }

            if (in_array($role, [Role::ADMIN, Role::MEMBER], true)) {
                $validator->errors()->add('role', AssignmentRules::USER_ROLE);
            }

            if ($role === Role::SUPER_ADMIN) {
                $validator->errors()->add('role', 'A SuperAdmin account is created only by the database seeder.');
            }

            if ($newCompany) {
                $validator->errors()->add('company_name', AssignmentRules::SINGLE_COMPANY);
            }

            if (! $newCompany && $actor->company_id === null) {
                $validator->errors()->add('company_name', 'The inviter must belong to the company.');
            }
        });

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $roleModel = Role::query()->where('name', $role)->firstOrFail();

        return Invitation::query()->create([
            'email' => $normalizedEmail,
            'role_id' => $roleModel->id,
            'company_id' => $actor->company_id,
            'invited_by' => $actor->id,
            'token' => Str::random(64),
        ]);
    }

    public function accept(string $token, string $name, string $password): User
    {
        $trimmedName = trim($name);

        return DB::transaction(function () use ($token, $trimmedName, $password) {
            $invitation = Invitation::query()
                ->with('role')
                ->where('token', $token)
                ->whereNull('accepted_at')
                ->lockForUpdate()
                ->first();

            $validator = Validator::make(
                [
                    'token' => $token,
                    'name' => $trimmedName,
                    'password' => $password,
                ],
                [
                    'token' => ['required', 'string'],
                    'name' => ['required', 'string', 'max:255'],
                    'password' => ['required', 'string', 'min:8'],
                ]
            );

            $validator->after(function ($validator) use ($invitation) {
                if ($invitation === null) {
                    $validator->errors()->add('token', 'This invitation is invalid or has already been used.');

                    return;
                }

                if (in_array($invitation->role->name, [Role::ADMIN, Role::MEMBER], true)) {
                    $validator->errors()->add('role', AssignmentRules::USER_ROLE);
                }

                if (User::query()->where('email', $invitation->email)->exists()) {
                    $validator->errors()->add('email', 'A user with this email already exists.');
                }
            });

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $user = User::query()->create([
                'name' => $trimmedName,
                'email' => $invitation->email,
                'password' => Hash::make($password),
                'company_id' => $invitation->company_id,
                'role_id' => $invitation->role_id,
                'email_verified_at' => now(),
            ]);

            $invitation->forceFill([
                'accepted_at' => now(),
            ])->save();

            return $user;
        });
    }
}
