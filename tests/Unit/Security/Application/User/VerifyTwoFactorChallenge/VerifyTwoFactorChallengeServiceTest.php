<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\User\VerifyTwoFactorChallenge;

use App\Security\Application\UnauthorizedActionException;
use App\Security\Application\User\TwoFactor\EffectiveTwoFactorMethod;
use App\Security\Application\User\VerifyTwoFactorChallenge\VerifyTwoFactorChallengeRequest;
use App\Security\Application\User\VerifyTwoFactorChallenge\VerifyTwoFactorChallengeService;
use App\Security\Domain\Model\User\TwoFactorChallenge;
use App\Security\Domain\Model\User\TwoFactorCodeInvalidException;
use App\Security\Domain\Model\User\TwoFactorMethod;
use App\Security\Domain\Model\User\TwoFactorNotPendingException;
use App\Security\Domain\Model\User\TwoFactorVerificationFailed;
use App\Security\Domain\Model\User\TwoFactorVerified;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Security\Infrastructure\Symfony\Security\TwoFactor\InMemoryTotpAuthenticator;
use App\Security\Infrastructure\Symfony\Security\TwoFactor\InMemoryTwoFactorPolicy;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Application\User\UserAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class VerifyTwoFactorChallengeServiceTest extends TestCase
{
    private const APP_SECRET = 'test-app-secret';

    private SpyDomainEventSubscriber $spy;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spy = new SpyDomainEventSubscriber();
    }

    public function testValidEmailCodePublishesTwoFactorVerified(): void
    {
        $repository = new InMemoryUserRepository();
        [$user, $code] = $this->userWithEmailChallenge($repository);

        $response = $this->createSUT($repository)(new VerifyTwoFactorChallengeRequest($user->id(), $code), $user);

        $this->assertEquals(TwoFactorMethod::Email, $response->method);
        $this->assertInstanceOf(TwoFactorVerified::class, $this->spy->lastEvent());
        $this->assertFalse($user->twoFactorChallenge()->isPending());
    }

    public function testWrongCodePersistsTheAttemptAndThrows(): void
    {
        $repository = new InMemoryUserRepository();
        [$user, $code] = $this->userWithEmailChallenge($repository);

        try {
            $this->createSUT($repository)(new VerifyTwoFactorChallengeRequest($user->id(), $this->wrongCode($code)), $user);
            $this->fail('A wrong code must throw');
        } catch (TwoFactorCodeInvalidException $e) {
            $this->assertEquals(2, $e->attemptsLeft());
            $this->assertFalse($e->isLocked());
        }

        $this->assertInstanceOf(TwoFactorVerificationFailed::class, $this->spy->lastEvent());
        $this->assertEquals(2, $repository->find($user->id())?->twoFactorChallenge()->attemptsLeft());
    }

    public function testThirdWrongCodeLocksTheChallenge(): void
    {
        $repository = new InMemoryUserRepository();
        [$user, $code] = $this->userWithEmailChallenge($repository);
        $sut = $this->createSUT($repository);

        $exception = null;

        for ($i = 0; $i < 3; ++$i) {
            try {
                $sut(new VerifyTwoFactorChallengeRequest($user->id(), $this->wrongCode($code)), $user);
            } catch (TwoFactorCodeInvalidException $e) {
                $exception = $e;
            }
        }

        $this->assertInstanceOf(TwoFactorCodeInvalidException::class, $exception);
        $this->assertTrue($exception->isLocked());

        $this->expectException(TwoFactorNotPendingException::class);

        $sut(new VerifyTwoFactorChallengeRequest($user->id(), $code), $user);
    }

    public function testExpiredChallenge(): void
    {
        $this->expectException(TwoFactorNotPendingException::class);

        $repository = new InMemoryUserRepository();
        $user = UserMother::withTwoFactorMethod('user@dediez.software', TwoFactorMethod::Email);
        $user->requestTwoFactorChallenge(
            TwoFactorMethod::Email,
            new TwoFactorChallenge(codeHash: 'hash', expiresAt: new \DateTimeImmutable('-1 minute'), attempts: 0),
            'u***@dediez.software',
        );
        $repository->add($user);

        $this->createSUT($repository)(new VerifyTwoFactorChallengeRequest($user->id(), '123456'), $user);
    }

    public function testValidTotpCode(): void
    {
        $repository = new InMemoryUserRepository();
        $user = $this->userWithTotpChallenge($repository);

        $response = $this->createSUT($repository)(new VerifyTwoFactorChallengeRequest($user->id(), InMemoryTotpAuthenticator::VALID_CODE), $user);

        $this->assertEquals(TwoFactorMethod::Totp, $response->method);
        $this->assertInstanceOf(TwoFactorVerified::class, $this->spy->lastEvent());
    }

    public function testWrongTotpCode(): void
    {
        $this->expectException(TwoFactorCodeInvalidException::class);

        $repository = new InMemoryUserRepository();
        $user = $this->userWithTotpChallenge($repository);

        $this->createSUT($repository)(new VerifyTwoFactorChallengeRequest($user->id(), '000000'), $user);
    }

    public function testUnauthorizedAccess(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = new InMemoryUserRepository();
        [$user, $code] = $this->userWithEmailChallenge($repository);

        $this->createSUT($repository)(new VerifyTwoFactorChallengeRequest($user->id(), $code), UserMother::ownerUser());
    }

    /**
     * @return array{User, string}
     */
    private function userWithEmailChallenge(InMemoryUserRepository $repository): array
    {
        $user = UserMother::withTwoFactorMethod('user@dediez.software', TwoFactorMethod::Email);
        $challenge = TwoFactorChallenge::generate(self::APP_SECRET);
        $user->requestTwoFactorChallenge(TwoFactorMethod::Email, $challenge, 'u***@dediez.software');
        $repository->add($user);

        return [$user, $challenge->plainCode()];
    }

    private function userWithTotpChallenge(InMemoryUserRepository $repository): User
    {
        $user = UserMother::withTwoFactorMethod('user@dediez.software', TwoFactorMethod::Totp);
        $user->requestTwoFactorChallenge(TwoFactorMethod::Totp, TwoFactorChallenge::withoutCode(), '');
        $repository->add($user);

        return $user;
    }

    private function wrongCode(string $code): string
    {
        return '000000' === $code ? '111111' : '000000';
    }

    private function createSUT(InMemoryUserRepository $repository): VerifyTwoFactorChallengeService
    {
        $totp = new InMemoryTotpAuthenticator();

        return new VerifyTwoFactorChallengeService(
            repository: $repository,
            authorizer: UserAuthorizerMother::create(),
            effectiveMethod: new EffectiveTwoFactorMethod(new InMemoryTwoFactorPolicy(), $totp),
            totp: $totp,
            appSecret: self::APP_SECRET,
        );
    }
}
