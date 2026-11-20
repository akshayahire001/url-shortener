<?php

namespace Tests\Unit;

use App\Contracts\CompanyServiceInterface;
use App\Contracts\InvitationServiceInterface;
use App\Contracts\ShortUrlServiceInterface;
use App\Services\CompanyService;
use App\Services\InvitationService;
use App\Services\ShortUrlService;
use Tests\TestCase;

class ServiceContainerTest extends TestCase
{
    public function test_service_provider_binds_interfaces_in_the_container(): void
    {
        $this->assertInstanceOf(CompanyService::class, app(CompanyServiceInterface::class));
        $this->assertInstanceOf(ShortUrlService::class, app(ShortUrlServiceInterface::class));
        $this->assertInstanceOf(InvitationService::class, app(InvitationServiceInterface::class));
    }
}
