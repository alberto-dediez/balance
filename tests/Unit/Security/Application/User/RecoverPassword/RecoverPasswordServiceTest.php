<?php

namespace App\Tests\Unit\Security\Application\User\RecoverPassword;

use App\Security\Application\User\RecoverPassword\RecoverPasswordRequest;
use App\Security\Application\User\RecoverPassword\RecoverPasswordResponse;
use App\Security\Application\User\RecoverPassword\RecoverPasswordService;
use App\Security\Domain\Model\User\PasswordRecovered;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Security\Infrastructure\Symfony\Security\Md5PasswordHasher;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RecoverPasswordServiceTest extends TestCase
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
        $user->initPasswordRecovery();

        $token = $user->recoverToken();

        $repository->add($user);

        $sut = $this->createSUT($repository);

        $request = new RecoverPasswordRequest(
            token: $token,
            password: '666'
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof RecoverPasswordResponse);
        $this->assertTrue($user->id()->equals($result->userId));

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof PasswordRecovered);
        $this->assertTrue($user->id()->equals($lastEvent->userId()));

        $userPersisted = $repository->find($result->userId);

        $this->assertNotNull($userPersisted);
        $this->assertTrue($userPersisted instanceof User);
        $this->assertNotEquals($request->password, $userPersisted->password());
    }

    public function testIsHandle()
    {
        $repository = new InMemoryUserRepository();

        $sut = $this->createSUT($repository);

        $user = UserMother::random();
        $user->initPasswordRecovery();

        $result = $sut->isHandled(new RecoverPasswordRequest(
            token: $user->recoverToken(),
            password: '333'
        ));

        $this->assertTrue($result);
    }

    private function createSUT($repository)
    {
        $passwordHasher = new Md5PasswordHasher();

        return new RecoverPasswordService($repository, $passwordHasher);
    }
}
