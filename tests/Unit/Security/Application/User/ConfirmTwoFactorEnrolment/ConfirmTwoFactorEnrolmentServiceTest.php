<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\User\ConfirmTwoFactorEnrolment;

use App\Security\Application\UnauthorizedActionException;
use App\Security\Application\User\ConfirmTwoFactorEnrolment\ConfirmTwoFactorEnrolmentRequest;
use App\Security\Application\User\ConfirmTwoFactorEnrolment\ConfirmTwoFactorEnrolmentService;
use App\Security\Domain\Model\User\TwoFactorCodeInvalidException;
use App\Security\Domain\Model\User\TwoFactorEnrolmentConfirmed;
use App\Security\Domain\Model\User\TwoFactorMethod;
use App\Security\Domain\Model\User\TwoFactorNotPendingException;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Security\Infrastructure\Symfony\Security\TwoFactor\InMemoryTotpAuthenticator;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Application\User\UserAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class ConfirmTwoFactorEnrolmentServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spy;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spy = new SpyDomainEventSubscriber();
    }

    public function testConfirmsTotp(): void
    {
        $repository = new InMemoryUserRepository();
        $user = $this->userWithPendingEnrolment($repository);

        $response = $this->createSUT($repository)(new ConfirmTwoFactorEnrolmentRequest($user->id(), InMemoryTotpAuthenticator::VALID_CODE), $user);

        $this->assertEquals(TwoFactorMethod::Totp, $response->method);
        $this->assertEquals(TwoFactorMethod::Totp, $user->twoFactorMethod());
        $this->assertInstanceOf(TwoFactorEnrolmentConfirmed::class, $this->spy->lastEvent());
    }

    public function testWrongCode(): void
    {
        $repository = new InMemoryUserRepository();
        $user = $this->userWithPendingEnrolment($repository);

        try {
            $this->createSUT($repository)(new ConfirmTwoFactorEnrolmentRequest($user->id(), '000000'), $user);
            $this->fail('A wrong code must throw');
        } catch (TwoFactorCodeInvalidException $e) {
            $this->assertEquals(2, $e->attemptsLeft());
        }

        $this->assertNull($user->twoFactorMethod());
        $this->assertEquals(2, $repository->find($user->id())?->twoFactorChallenge()->attemptsLeft());
    }

    public function testWithoutPendingEnrolment(): void
    {
        $this->expectException(TwoFactorNotPendingException::class);

        $repository = new InMemoryUserRepository();
        $user = UserMother::fromUsername('user@dediez.software');
        $repository->add($user);

        $this->createSUT($repository)(new ConfirmTwoFactorEnrolmentRequest($user->id(), InMemoryTotpAuthenticator::VALID_CODE), $user);
    }

    public function testUnauthorizedAccess(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = new InMemoryUserRepository();
        $user = $this->userWithPendingEnrolment($repository);

        $this->createSUT($repository)(new ConfirmTwoFactorEnrolmentRequest($user->id(), InMemoryTotpAuthenticator::VALID_CODE), UserMother::ownerUser());
    }

    private function userWithPendingEnrolment(InMemoryUserRepository $repository): User
    {
        $user = UserMother::fromUsername('user@dediez.software');
        $user->requestTotpEnrolment(InMemoryTotpAuthenticator::SECRET);
        $repository->add($user);

        return $user;
    }

    private function createSUT(InMemoryUserRepository $repository): ConfirmTwoFactorEnrolmentService
    {
        return new ConfirmTwoFactorEnrolmentService(
            repository: $repository,
            authorizer: UserAuthorizerMother::create(),
            totp: new InMemoryTotpAuthenticator(),
        );
    }
}
