<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Timezone;

use App\Shared\Infrastructure\Timezone\CookieTimezoneProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CookieTimezoneProviderTest extends TestCase
{
    public function testReturnsTimezoneFromCookie(): void
    {
        $provider = $this->providerWithCookie('Europe/Madrid');

        $this->assertSame('Europe/Madrid', $provider->currentTimezone()->getName());
    }

    public function testFallsBackToDefaultTimezoneWhenCookieIsInvalid(): void
    {
        $provider = $this->providerWithCookie('Mars/Olympus');

        $this->assertSame(date_default_timezone_get(), $provider->currentTimezone()->getName());
    }

    public function testFallsBackToDefaultTimezoneWithoutCookie(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $this->assertSame(date_default_timezone_get(), (new CookieTimezoneProvider($requestStack))->currentTimezone()->getName());
    }

    public function testFallsBackToDefaultTimezoneWithoutRequest(): void
    {
        $this->assertSame(date_default_timezone_get(), (new CookieTimezoneProvider(new RequestStack()))->currentTimezone()->getName());
    }

    private function providerWithCookie(string $timezone): CookieTimezoneProvider
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request(cookies: [CookieTimezoneProvider::COOKIE => $timezone]));

        return new CookieTimezoneProvider($requestStack);
    }
}
