<?php

namespace App\Tests\Unit\Security\Application\User\RecoverToken;

use PHPUnit\Framework\TestCase;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Security\Infrastructure\Repository\User\InMemoryUserRepository;
use App\Security\Application\User\RecoverToken\RecoverTokenRequest;
use App\Security\Application\User\RecoverToken\RecoverTokenService;
use App\Security\Domain\Model\User\PasswordRecoveryInitialized;

class RecoverTokenServiceTest extends TestCase
{
    private $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase()
    {
        $repository = new InMemoryUserRepository();

        $user = UserMother::random();

        $repository->add($user);

        $sut = $this->createSUT($repository);

        $request = new RecoverTokenRequest(
            username: $user->username()
        );

        $sut($request);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof PasswordRecoveryInitialized);
        $this->assertTrue($user->id()->equals($lastEvent->userId()));
    }

    public function testNotFoundUser()
    {
        $this->expectException(\InvalidArgumentException::class);

        $repository = new InMemoryUserRepository();

        $user = UserMother::random();

        $repository->add($user);

        $sut = $this->createSUT($repository);

        $request = new RecoverTokenRequest(
            username: 'unknown username',
        );

        $sut($request);
    }

    private function createSUT($repository)
    {
        return new RecoverTokenService($repository);
    }
}