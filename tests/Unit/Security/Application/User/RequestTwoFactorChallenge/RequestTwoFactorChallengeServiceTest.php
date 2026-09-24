<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\User\RequestTwoFactorChallenge;

use App\Security\Application\UnauthorizedActionException;
use App\Security\Application\User\RequestTwoFactorChallenge\RequestTwoFactorChallengeRequest;
use App\Security\Application\User\RequestTwoFactorChallenge\RequestTwoFactorChallengeService;
use App\Security\Application\User\TwoFactor\EffectiveTwoFactorMethod;
use App\Security\Domain\Model\User\TwoFactorChallengeRequested;
use App\Security\Domain\Model\User\TwoFactorCodeInvalidException;
use App\Security\Domain\Model\User\TwoFactorMethod;
use App\Security\Domain\Model\User\TwoFactorNotPendingException;
use App\Security\Domain\Model\User\UserId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Security\Infrastructure\Symfony\Security\TwoFactor\InMemoryTotpAuthenticator;
use App\Security\Infrastructure\Symfony\Security\TwoFactor\InMemoryTwoFactorPolicy;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Application\User\UserAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RequestTwoFactorChallengeServiceTest extends TestCase
{
    private const APP_SECRET = 'test-app-secret';

    private SpyDomainEventSubscriber $spy;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spy = new SpyDomainEventSubscriber();
    }

    public function testEmailGeneratesAndPublishesACode(): void
    {
        $repository = new InMemoryUserRepository();
        $user = UserMother::withTwoFactorMethod('user@dediez.software', TwoFactorMethod::Email);
        $repository->add($user);

        $response = $this->createSUT($repository)(new RequestTwoFactorChallengeRequest($user->id()), $user);

        $this->assertEquals(TwoFactorMethod::Email, $response->method);
        $this->assertEquals('u***@dediez.software', $response->maskedDestination);
        $this->assertFalse($response->isFallback);
        $this->assertNotNull($response->expiresAt);

        $event = $this->spy->lastEvent();
        $this->assertInstanceOf(TwoFactorChallengeRequested::class, $event);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $event->challenge()->plainCode());
        $this->assertTrue($user->twoFactorChallenge()->isPending());
    }

    public function testDoesNotRegenerateAPendingChallenge(): void
    {
        $repository = new InMemoryUserRepository();
        $user = UserMother::withTwoFactorMethod('user@dediez.software', TwoFactorMethod::Email);
        $repository->add($user);

        $sut = $this->createSUT($repository);
        $sut(new RequestTwoFactorChallengeRequest($user->id()), $user);
        $codeHash = $user->twoFactorChallenge()->codeHash();

        $sut(new RequestTwoFactorChallengeRequest($user->id()), $user);

        $this->assertCount(1, $this->spy->events());
        $this->assertEquals($codeHash, $user->twoFactorChallenge()->codeHash());
    }

    public function testForceRegeneratesThePendingChallenge(): void
    {
        $repository = new InMemoryUserRepository();
        $user = UserMother::withTwoFactorMethod('user@dediez.software', TwoFactorMethod::Email);
        $repository->add($user);

        $sut = $this->createSUT($repository);
        $sut(new RequestTwoFactorChallengeRequest($user->id()), $user);

        $sut(new RequestTwoFactorChallengeRequest($user->id(), force: true), $user);

        $this->assertCount(2, $this->spy->events());
        $this->assertInstanceOf(TwoFactorChallengeRequested::class, $this->spy->lastEvent());
    }

    public function testForceDoesNotRegenerateAPendingTotpChallenge(): void
    {
        $repository = new InMemoryUserRepository();
        $user = UserMother::withTwoFactorMethod('user@dediez.software', TwoFactorMethod::Totp);
        $repository->add($user);

        $sut = $this->createSUT($repository);
        $sut(new RequestTwoFactorChallengeRequest($user->id()), $user);

        try {
            $user->verifyTwoFactor('000000', TwoFactorMethod::Totp, self::APP_SECRET, new InMemoryTotpAuthenticator());
        } catch (TwoFactorCodeInvalidException) {
        }

        $expiresAt = $user->twoFactorChallenge()->expiresAt();

        $response = $sut(new RequestTwoFactorChallengeRequest($user->id(), force: true), $user);

        $this->assertEquals(TwoFactorMethod::Totp, $response->method);
        $this->assertEquals(2, $user->twoFactorChallenge()->attemptsLeft());
        $this->assertEquals($expiresAt, $user->twoFactorChallenge()->expiresAt());
        $this->assertCount(1, array_filter($this->spy->events(), static fn(object $event): bool => $event instanceof TwoFactorChallengeRequested));
    }

    public function testTotpPublishesAChallengeWithoutCode(): void
    {
        $repository = new InMemoryUserRepository();
        $user = UserMother::withTwoFactorMethod('user@dediez.software', TwoFactorMethod::Totp);
        $repository->add($user);

        $response = $this->createSUT($repository)(new RequestTwoFactorChallengeRequest($user->id()), $user);

        $this->assertEquals(TwoFactorMethod::Totp, $response->method);
        $this->assertSame('', $response->maskedDestination);

        $event = $this->spy->lastEvent();
        $this->assertInstanceOf(TwoFactorChallengeRequested::class, $event);
        $this->assertSame('', $event->challenge()->plainCode());
        $this->assertTrue($user->twoFactorChallenge()->isPending());
    }

    public function testUserWithoutTwoFactorInOptionalMode(): void
    {
        $this->expectException(TwoFactorNotPendingException::class);

        $repository = new InMemoryUserRepository();
        $user = UserMother::registeredUser();
        $repository->add($user);

        $this->createSUT($repository)(new RequestTwoFactorChallengeRequest($user->id()), $user);
    }

    public function testUserWithoutMethodInRequiredModeReceivesAnEmail(): void
    {
        $repository = new InMemoryUserRepository();
        $user = UserMother::fromUsername('user@dediez.software');
        $repository->add($user);

        $response = $this->createSUT($repository, new InMemoryTwoFactorPolicy(required: true))(new RequestTwoFactorChallengeRequest($user->id()), $user);

        $this->assertEquals(TwoFactorMethod::Email, $response->method);
        $this->assertInstanceOf(TwoFactorChallengeRequested::class, $this->spy->lastEvent());
    }

    public function testUnreadableSecretFallsBackToEmail(): void
    {
        $repository = new InMemoryUserRepository();
        $user = UserMother::withTwoFactorMethod('user@dediez.software', TwoFactorMethod::Totp, InMemoryTotpAuthenticator::UNREADABLE_SECRET);
        $repository->add($user);

        $response = $this->createSUT($repository)(new RequestTwoFactorChallengeRequest($user->id()), $user);

        $this->assertEquals(TwoFactorMethod::Email, $response->method);
        $this->assertTrue($response->isFallback);
        $this->assertEquals(TwoFactorMethod::Totp, $user->twoFactorMethod());

        $event = $this->spy->lastEvent();
        $this->assertInstanceOf(TwoFactorChallengeRequested::class, $event);
        $this->assertNotEmpty($event->challenge()->plainCode());
    }

    public function testUnauthorizedAccess(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = new InMemoryUserRepository();
        $user = UserMother::withTwoFactorMethod('user@dediez.software', TwoFactorMethod::Email);
        $repository->add($user);

        $this->createSUT($repository)(new RequestTwoFactorChallengeRequest($user->id()), UserMother::ownerUser());
    }

    public function testUserNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->createSUT(new InMemoryUserRepository())(new RequestTwoFactorChallengeRequest(UserId::generate()), UserMother::adminUser());
    }

    private function createSUT(InMemoryUserRepository $repository, ?InMemoryTwoFactorPolicy $policy = null): RequestTwoFactorChallengeService
    {
        return new RequestTwoFactorChallengeService(
            repository: $repository,
            authorizer: UserAuthorizerMother::create(),
            effectiveMethod: new EffectiveTwoFactorMethod($policy ?? new InMemoryTwoFactorPolicy(), new InMemoryTotpAuthenticator()),
            appSecret: self::APP_SECRET,
        );
    }
}
