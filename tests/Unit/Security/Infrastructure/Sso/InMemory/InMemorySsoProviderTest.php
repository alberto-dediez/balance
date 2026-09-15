<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Infrastructure\Sso\InMemory;

use App\Security\Infrastructure\Sso\InMemory\InMemorySsoProvider;
use App\Security\Infrastructure\Sso\SsoAuthenticationFailedException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class InMemorySsoProviderTest extends TestCase
{
    public function testFailsWhenNoIdentityWasConfigured(): void
    {
        $sut = new InMemorySsoProvider('google', 'Google');

        $this->expectException(SsoAuthenticationFailedException::class);

        $sut->authenticate(new Request());
    }

    public function testReturnsTheConfiguredIdentity(): void
    {
        $sut = new InMemorySsoProvider('google', 'Google');
        $sut->identifyAs('user@dediez.software');

        $identity = $sut->authenticate(new Request());

        $this->assertEquals('user@dediez.software', $identity->email);
    }

    public function testNormalisesTheConfiguredEmail(): void
    {
        $sut = new InMemorySsoProvider('google', 'Google');
        $sut->identifyAs('User@Dediez.Software');

        $identity = $sut->authenticate(new Request());

        $this->assertEquals('user@dediez.software', $identity->email);
    }

    public function testFailsWhenConfiguredToFail(): void
    {
        $sut = new InMemorySsoProvider('google', 'Google');
        $sut->identifyAs('user@dediez.software');
        $sut->fail();

        $this->expectException(SsoAuthenticationFailedException::class);

        $sut->authenticate(new Request());
    }

    public function testResetRestoresTheInitialState(): void
    {
        $sut = new InMemorySsoProvider('google', 'Google');
        $sut->identifyAs('user@dediez.software');
        $sut->fail();
        $sut->disable();

        $sut->reset();

        $this->assertTrue($sut->isEnabled());

        $this->expectException(SsoAuthenticationFailedException::class);

        $sut->authenticate(new Request());
    }

    public function testExposesKeyLabelAndLoginUrl(): void
    {
        $sut = new InMemorySsoProvider('microsoft', 'Microsoft');

        $this->assertEquals('microsoft', $sut->key());
        $this->assertEquals('Microsoft', $sut->label());
        $this->assertEquals('https://sso.test/microsoft', $sut->loginUrl());
    }
}
