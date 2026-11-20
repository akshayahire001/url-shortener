<?php

namespace App\Services;

use App\Contracts\ShortUrlServiceInterface;
use App\Models\Role;
use App\Models\ShortUrl;
use App\Models\User;
use App\Support\AssignmentRules;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ShortUrlService implements ShortUrlServiceInterface
{
    public function create(User $actor, string $originalUrl): ShortUrl
    {
        $blockedReason = $actor->creationBlockedReason();

        if ($blockedReason !== null) {
            throw new AuthorizationException($blockedReason);
        }

        $normalizedUrl = trim($originalUrl);

        $validator = Validator::make(
            ['original_url' => $normalizedUrl],
            ['original_url' => ['required', 'string', 'url:http,https', 'max:2048']]
        );

        $validator->after(function ($validator) use ($actor) {
            if ($actor->company_id === null) {
                $validator->errors()->add('original_url', 'A short URL must belong to a company.');
            }
        });

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return ShortUrl::query()->create([
            'company_id' => $actor->company_id,
            'user_id' => $actor->id,
            'code' => $this->generateUniqueCode(),
            'original_url' => $normalizedUrl,
        ]);
    }

    public function listFor(User $actor): Collection
    {
        if ($actor->hasRole(Role::SUPER_ADMIN)) {
            throw new AuthorizationException(AssignmentRules::SUPER_ADMIN_LIST);
        }

        $query = ShortUrl::query()
            ->select(['id', 'company_id', 'user_id', 'code', 'original_url', 'created_at'])
            ->with(['user:id,name', 'company:id,name'])
            ->latest('id');

        // Assignment intentionally inverts visibility per role:
        // Admin sees URLs outside their company; Member sees URLs not created by themselves.
        if ($actor->hasRole(Role::ADMIN)) {
            return $query
                ->where('company_id', '!=', $actor->company_id)
                ->get();
        }

        if ($actor->hasRole(Role::MEMBER)) {
            return $query
                ->where('user_id', '!=', $actor->id)
                ->get();
        }

        return $query
            ->where('company_id', $actor->company_id)
            ->get();
    }

    public function denyPublicAccess(string $code): never
    {
        throw new NotFoundHttpException(AssignmentRules::NOT_PUBLIC);
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = Str::lower(Str::random(8));
        } while (ShortUrl::query()->where('code', $code)->exists());

        return $code;
    }
}
