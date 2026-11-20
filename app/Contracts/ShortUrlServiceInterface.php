<?php

namespace App\Contracts;

use App\Models\ShortUrl;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface ShortUrlServiceInterface
{
    public function create(User $actor, string $originalUrl): ShortUrl;

    public function listFor(User $actor): Collection;

    public function denyPublicAccess(string $code): never;
}
