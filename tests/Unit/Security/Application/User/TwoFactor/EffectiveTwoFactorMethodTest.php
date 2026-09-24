<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\User\TwoFactor;

use App\Security\Application\User\TwoFactor\EffectiveTwoFactorMethod;
use App\Security\Domain\Model\User\TwoFactorMethod;
use App\Security\Infrastructure\Symfony\Security\TwoFactor\InMemoryTotpAuthenticator;
use App\Security\Infrastructure\Symfony\Security\TwoFactor\InMemoryTwoFactorPolicy;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class EffectiveTwoFactorMethodTest extends TestCase
{
    public function testOptionalModeWithoutMethodHasNoTwoFactor(): void
    {
        $sut = $this->createSUT(new InMemoryTwoFactorPolicy());

        $this->assertNull($sut->resolve(UserMother::registeredUser()));
    }

    public function testOptionalModeWithEmail(): void
    {
        $sut = $this->createSUT(new InMemoryTwoFactorPolicy());

        $user = UserMother::withTwoFactorMethod('user@dediez.software', TwoFactorMethod::Email);

        $this->assertEquals(TwoFactorMethod::Email, $sut->resolve($user));
        $this->assertFalse($sut->isFallback($user));
    }

    public function testOptionalModeWithReadableTotp(): void
    {
        $sut = $this->createSUT(new InMemoryTwoFactorPolicy());

        $user = UserMother::withTwoFactorMethod('user@dediez.software', TwoFactorMethod::Totp);

        $this->assertEquals(TwoFactorMethod::Totp, $sut->resolve($user));
        $this->assertFalse($sut->isFallback($user));
    }

    public function testOptionalModeWithUnreadableTotpFallsBackToEmail(): void
    {
        $sut = $this->createSUT(new InMemoryTwoFactorPolicy());

        $user = UserMother::withTwoFactorMethod('user@dediez.software', TwoFactorMethod::Totp, InMemoryTotpAuthenticator::UNREADABLE_SECRET);

        $this->assertEquals(TwoFactorMethod::Email, $sut->resolve($user));
        $this->assertTrue($sut->isFallback($user));
        $this->assertEquals(TwoFactorMethod::Totp, $user->twoFactorMethod());
    }

    public function testRequiredModeWithoutMethodUsesEmail(): void
    {
        $sut = $this->createSUT(new InMemoryTwoFactorPolicy(required: true));

        $this->assertEquals(TwoFactorMethod::Email, $sut->resolve(UserMother::registeredUser()));
    }

    public function testRequiredModeKeepsTheStoredMethod(): void
    {
        $sut = $this->createSUT(new InMemoryTwoFactorPolicy(required: true));

        $this->assertEquals(TwoFactorMethod::Totp, $sut->resolve(UserMother::withTwoFactorMethod('totp@dediez.software', TwoFactorMethod::Totp)));
        $this->assertEquals(TwoFactorMethod::Email, $sut->resolve(UserMother::withTwoFactorMethod('email@dediez.software', TwoFactorMethod::Email)));
    }

    public function testSsoWithSsoFlagDisabledHasNoTwoFactor(): void
    {
        $sut = $this->createSUT(new InMemoryTwoFactorPolicy(required: true));

        $this->assertNull($sut->resolveForSso(UserMother::withTwoFactorMethod('user@dediez.software', TwoFactorMethod::Totp)));
    }

    public function testSsoWithSsoFlagEnabledAndReadableTotp(): void
    {
        $sut = $this->createSUT(new InMemoryTwoFactorPolicy(ssoEnabled: true));

        $this->assertEquals(TwoFactorMethod::Totp, $sut->resolveForSso(UserMother::withTwoFactorMethod('user@dediez.software', TwoFactorMethod::Totp)));
    }

    public function testSsoWithSsoFlagEnabledAndAnyOtherMethodHasNoTwoFactor(): void
    {
        $sut = $this->createSUT(new InMemoryTwoFactorPolicy(required: true, ssoEnabled: true));

        $this->assertNull($sut->resolveForSso(UserMother::registeredUser()));
        $this->assertNull($sut->resolveForSso(UserMother::withTwoFactorMethod('email@dediez.software', TwoFactorMethod::Email)));
        $this->assertNull($sut->resolveForSso(UserMother::withTwoFactorMethod('totp@dediez.software', TwoFactorMethod::Totp, InMemoryTotpAuthenticator::UNREADABLE_SECRET)));
    }

    private function createSUT(InMemoryTwoFactorPolicy $policy): EffectiveTwoFactorMethod
    {
        return new EffectiveTwoFactorMethod($policy, new InMemoryTotpAuthenticator());
    }
}
