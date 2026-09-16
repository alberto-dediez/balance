<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Infrastructure\Sso;

use App\Security\Infrastructure\Sso\InMemory\InMemorySsoProvider;
use App\Security\Infrastructure\Sso\SsoProviderRegistry;
use App\Security\Infrastructure\Sso\UnknownSsoProviderException;
use PHPUnit\Framework\TestCase;

class SsoProviderRegistryTest extends TestCase
{
    public function testReturnsTheProviderRegisteredWithTheGivenKey(): void
    {
        $google = new InMemorySsoProvider('google', 'Google');
        $sut = new SsoProviderRegistry([$google, new InMemorySsoProvider('microsoft', 'Microsoft')]);

        $this->assertSame($google, $sut->get('google'));
    }

    public function testFailsWithAnUnknownKey(): void
    {
        $sut = new SsoProviderRegistry([new InMemorySsoProvider('google', 'Google')]);

        $this->expectException(UnknownSsoProviderException::class);

        $sut->get('github');
    }

    public function testReturnsOnlyTheEnabledProvidersInRegistrationOrder(): void
    {
        $google = new InMemorySsoProvider('google', 'Google');
        $microsoft = new InMemorySsoProvider('microsoft', 'Microsoft');
        $microsoft->disable();

        $sut = new SsoProviderRegistry([$google, $microsoft]);

        $this->assertSame([$google], $sut->enabled());
    }

    public function testReturnsEveryRegisteredProvider(): void
    {
        $google = new InMemorySsoProvider('google', 'Google');
        $microsoft = new InMemorySsoProvider('microsoft', 'Microsoft');
        $microsoft->disable();

        $sut = new SsoProviderRegistry([$google, $microsoft]);

        $this->assertSame([$google, $microsoft], $sut->all());
    }
}
