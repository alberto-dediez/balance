<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Model\User;

use App\Security\Domain\Model\User\TwoFactorChallenge;
use App\Security\Domain\Model\User\TwoFactorChallengeRequested;
use App\Security\Domain\Model\User\TwoFactorCodeInvalidException;
use App\Security\Domain\Model\User\TwoFactorDisabled;
use App\Security\Domain\Model\User\TwoFactorEnrolmentCancelled;
use App\Security\Domain\Model\User\TwoFactorEnrolmentConfirmed;
use App\Security\Domain\Model\User\TwoFactorEnrolmentRequested;
use App\Security\Domain\Model\User\TwoFactorMethod;
use App\Security\Domain\Model\User\TwoFactorNotPendingException;
use App\Security\Domain\Model\User\TwoFactorVerificationFailed;
use App\Security\Domain\Model\User\TwoFactorVerified;
use App\Security\Infrastructure\Symfony\Security\TwoFactor\InMemoryTotpAuthenticator;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UserTwoFactorTest extends TestCase
{
    private const APP_SECRET = 'test-app-secret';

    private SpyDomainEventSubscriber $spy;

    private InMemoryTotpAuthenticator $totp;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spy = new SpyDomainEventSubscriber();
        $this->totp = new InMemoryTotpAuthenticator();
    }

    public function testEnableTwoFactorByEmail(): void
    {
        $user = UserMother::registeredUser();

        $user->enableTwoFactorByEmail();

        $this->assertEquals(TwoFactorMethod::Email, $user->twoFactorMethod());
        $this->assertTrue($user->hasTwoFactor());

        $event = $this->spy->lastEvent();
        $this->assertInstanceOf(TwoFactorEnrolmentConfirmed::class, $event);
        $this->assertEquals(TwoFactorMethod::Email, $event->method());
    }

    public function testRequestTotpEnrolmentKeepsThePendingSecretAndChallenge(): void
    {
        $user = UserMother::registeredUser();

        $user->requestTotpEnrolment(InMemoryTotpAuthenticator::SECRET);

        $this->assertNull($user->twoFactorMethod());
        $this->assertTrue($user->hasPendingTotpEnrolment());
        $this->assertEquals(InMemoryTotpAuthenticator::SECRET, $user->pendingTotpSecret());
        $this->assertTrue($user->twoFactorChallenge()->isPending());

        $event = $this->spy->lastEvent();
        $this->assertInstanceOf(TwoFactorEnrolmentRequested::class, $event);
        $this->assertArrayNotHasKey('encryptedSecret', $event->payload());
        $this->assertStringNotContainsString(InMemoryTotpAuthenticator::SECRET, (string) json_encode($event->payload()));
    }

    public function testConfirmTotpEnrolmentWithAValidCode(): void
    {
        $user = UserMother::registeredUser();
        $user->requestTotpEnrolment(InMemoryTotpAuthenticator::SECRET);

        $user->confirmTotpEnrolment(InMemoryTotpAuthenticator::VALID_CODE, $this->totp);

        $this->assertEquals(TwoFactorMethod::Totp, $user->twoFactorMethod());
        $this->assertEquals(InMemoryTotpAuthenticator::SECRET, $user->totpSecret());
        $this->assertNull($user->pendingTotpSecret());
        $this->assertFalse($user->twoFactorChallenge()->isPending());

        $event = $this->spy->lastEvent();
        $this->assertInstanceOf(TwoFactorEnrolmentConfirmed::class, $event);
        $this->assertEquals(TwoFactorMethod::Totp, $event->method());
    }

    public function testConfirmTotpEnrolmentWithAWrongCode(): void
    {
        $user = UserMother::registeredUser();
        $user->requestTotpEnrolment(InMemoryTotpAuthenticator::SECRET);

        try {
            $user->confirmTotpEnrolment('000000', $this->totp);
            $this->fail('An invalid code must throw');
        } catch (TwoFactorCodeInvalidException $e) {
            $this->assertEquals(2, $e->attemptsLeft());
            $this->assertFalse($e->isLocked());
        }

        $this->assertInstanceOf(TwoFactorVerificationFailed::class, $this->spy->lastEvent());
        $this->assertNull($user->twoFactorMethod());
        $this->assertTrue($user->hasPendingTotpEnrolment());
    }

    public function testConfirmTotpEnrolmentIsLockedAfterTheThirdFailure(): void
    {
        $user = UserMother::registeredUser();
        $user->requestTotpEnrolment(InMemoryTotpAuthenticator::SECRET);

        $exception = null;

        for ($i = 0; $i < 3; ++$i) {
            try {
                $user->confirmTotpEnrolment('000000', $this->totp);
            } catch (TwoFactorCodeInvalidException $e) {
                $exception = $e;
            }
        }

        $this->assertInstanceOf(TwoFactorCodeInvalidException::class, $exception);
        $this->assertTrue($exception->isLocked());
        $this->assertTrue($user->twoFactorChallenge()->isLocked());

        $this->expectException(TwoFactorNotPendingException::class);

        $user->confirmTotpEnrolment(InMemoryTotpAuthenticator::VALID_CODE, $this->totp);
    }

    public function testConfirmTotpEnrolmentWithoutEnrolment(): void
    {
        $this->expectException(TwoFactorNotPendingException::class);

        $user = UserMother::registeredUser();

        $user->confirmTotpEnrolment(InMemoryTotpAuthenticator::VALID_CODE, $this->totp);
    }

    public function testChangingFromTotpToEmailRemovesTheSecret(): void
    {
        $user = UserMother::withTwoFactorMethod('user@dediez.software', TwoFactorMethod::Totp);

        $this->assertNotNull($user->totpSecret());

        $user->enableTwoFactorByEmail();

        $this->assertEquals(TwoFactorMethod::Email, $user->twoFactorMethod());
        $this->assertNull($user->totpSecret());
    }

    public function testCancelTotpEnrolmentKeepsTheCurrentMethod(): void
    {
        $user = UserMother::withTwoFactorMethod('user@dediez.software', TwoFactorMethod::Totp, 'current-secret');
        $user->requestTotpEnrolment('another-secret');

        $user->cancelTotpEnrolment();

        $this->assertEquals(TwoFactorMethod::Totp, $user->twoFactorMethod());
        $this->assertEquals('current-secret', $user->totpSecret());
        $this->assertNull($user->pendingTotpSecret());
        $this->assertFalse($user->twoFactorChallenge()->isPending());
        $this->assertInstanceOf(TwoFactorEnrolmentCancelled::class, $this->spy->lastEvent());
    }

    public function testCancelTotpEnrolmentDoesNothingWithoutAPendingEnrolment(): void
    {
        $user = UserMother::withTwoFactorMethod('user@dediez.software', TwoFactorMethod::Email);

        $user->cancelTotpEnrolment();

        $this->assertEquals(TwoFactorMethod::Email, $user->twoFactorMethod());
        $this->assertNull($this->spy->lastEvent());
    }

    public function testDisableTwoFactorClearsEverything(): void
    {
        $user = UserMother::withTwoFactorMethod('user@dediez.software', TwoFactorMethod::Totp);
        $user->requestTotpEnrolment('another-secret');

        $user->disableTwoFactor();

        $this->assertNull($user->twoFactorMethod());
        $this->assertNull($user->totpSecret());
        $this->assertNull($user->pendingTotpSecret());
        $this->assertFalse($user->twoFactorChallenge()->isPending());
        $this->assertInstanceOf(TwoFactorDisabled::class, $this->spy->lastEvent());
    }

    public function testDisableTwoFactorDoesNothingWithoutTwoFactor(): void
    {
        $user = UserMother::registeredUser();

        $user->disableTwoFactor();

        $this->assertNull($this->spy->lastEvent());
    }

    public function testVerifyTwoFactorByEmail(): void
    {
        $user = UserMother::withTwoFactorMethod('user@dediez.software', TwoFactorMethod::Email);
        $challenge = TwoFactorChallenge::generate(self::APP_SECRET);

        $user->requestTwoFactorChallenge(TwoFactorMethod::Email, $challenge, 'u***@dediez.software');

        $requested = $this->spy->lastEvent();
        $this->assertInstanceOf(TwoFactorChallengeRequested::class, $requested);
        $this->assertEquals($challenge->plainCode(), $requested->challenge()->plainCode());
        $this->assertStringNotContainsString($challenge->plainCode(), (string) json_encode($requested->payload()));

        $user->verifyTwoFactor($challenge->plainCode(), TwoFactorMethod::Email, self::APP_SECRET, $this->totp);

        $event = $this->spy->lastEvent();
        $this->assertInstanceOf(TwoFactorVerified::class, $event);
        $this->assertEquals(TwoFactorMethod::Email, $event->method());
        $this->assertFalse($user->twoFactorChallenge()->isPending());
    }

    public function testVerifyTwoFactorWithoutChallenge(): void
    {
        $this->expectException(TwoFactorNotPendingException::class);

        $user = UserMother::withTwoFactorMethod('user@dediez.software', TwoFactorMethod::Email);

        $user->verifyTwoFactor('123456', TwoFactorMethod::Email, self::APP_SECRET, $this->totp);
    }

    public function testVerifyTwoFactorByTotpUsesTheStoredSecretAndCountsAttempts(): void
    {
        $user = UserMother::withTwoFactorMethod('user@dediez.software', TwoFactorMethod::Totp);
        $user->requestTwoFactorChallenge(TwoFactorMethod::Totp, TwoFactorChallenge::withoutCode(), '');

        try {
            $user->verifyTwoFactor('000000', TwoFactorMethod::Totp, self::APP_SECRET, $this->totp);
            $this->fail('An invalid code must throw');
        } catch (TwoFactorCodeInvalidException $e) {
            $this->assertEquals(2, $e->attemptsLeft());
        }

        $this->assertEquals(2, $user->twoFactorChallenge()->attemptsLeft());

        $user->verifyTwoFactor(InMemoryTotpAuthenticator::VALID_CODE, TwoFactorMethod::Totp, self::APP_SECRET, $this->totp);

        $this->assertInstanceOf(TwoFactorVerified::class, $this->spy->lastEvent());
    }
}
