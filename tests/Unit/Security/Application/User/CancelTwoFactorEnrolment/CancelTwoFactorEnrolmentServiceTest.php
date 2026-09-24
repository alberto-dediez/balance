<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\User\CancelTwoFactorEnrolment;

use App\Security\Application\UnauthorizedActionException;
use App\Security\Application\User\CancelTwoFactorEnrolment\CancelTwoFactorEnrolmentRequest;
use App\Security\Application\User\CancelTwoFactorEnrolment\CancelTwoFactorEnrolmentService;
use App\Security\Domain\Model\User\TwoFactorEnrolmentCancelled;
use App\Security\Domain\Model\User\TwoFactorMethod;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Application\User\UserAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class CancelTwoFactorEnrolmentServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spy;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spy = new SpyDomainEventSubscriber();
    }

    public function testCancelsThePendingEnrolmentKeepingTheCurrentMethod(): void
    {
        $repository = new InMemoryUserRepository();
        $user = UserMother::withTwoFactorMethod('user@dediez.software', TwoFactorMethod::Totp, 'current-secret');
        $user->requestTotpEnrolment('another-secret');
        $repository->add($user);

        $response = $this->createSUT($repository)(new CancelTwoFactorEnrolmentRequest($user->id()), $user);

        $this->assertTrue($user->id()->equals($response->userId));
        $this->assertEquals(TwoFactorMethod::Totp, $user->twoFactorMethod());
        $this->assertEquals('current-secret', $user->totpSecret());
        $this->assertFalse($user->hasPendingTotpEnrolment());
        $this->assertInstanceOf(TwoFactorEnrolmentCancelled::class, $this->spy->lastEvent());
    }

    public function testUserCannotCancelAnotherUserEnrolment(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = new InMemoryUserRepository();
        $user = UserMother::withTwoFactorMethod('user@dediez.software', TwoFactorMethod::Email);
        $user->requestTotpEnrolment('another-secret');
        $repository->add($user);

        $this->createSUT($repository)(new CancelTwoFactorEnrolmentRequest($user->id()), UserMother::fromUsername('other@dediez.software'));
    }

    private function createSUT(InMemoryUserRepository $repository): CancelTwoFactorEnrolmentService
    {
        return new CancelTwoFactorEnrolmentService(
            repository: $repository,
            authorizer: UserAuthorizerMother::create(),
        );
    }
}
