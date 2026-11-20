<?php

namespace App\Providers;

use App\Contracts\CompanyServiceInterface;
use App\Contracts\InvitationServiceInterface;
use App\Contracts\ShortUrlServiceInterface;
use App\Services\CompanyService;
use App\Services\InvitationService;
use App\Services\ShortUrlService;
use Illuminate\Support\ServiceProvider;

class UrlShortenerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CompanyServiceInterface::class, CompanyService::class);
        $this->app->singleton(ShortUrlServiceInterface::class, ShortUrlService::class);
        $this->app->singleton(InvitationServiceInterface::class, InvitationService::class);
    }
}
