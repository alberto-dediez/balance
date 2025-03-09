<?php

namespace App\Tests\Unit\Security\Application\User\ChangePassword;

use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Security\Domain\Model\User\PasswordChanged;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Security\Infrastructure\Symfony\Security\Md5PasswordHasher;
use App\Security\Application\User\ChangePassword\ChangePasswordRequest;
use App\Security\Application\User\ChangePassword\ChangePasswordService;
use App\Security\Infrastructure\Repository\User\InMemoryUserRepository;
use App\Security\Application\User\ChangePassword\ChangePasswordResponse;

class ChangePasswordServiceTest extends TestCase
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

        $request = new ChangePasswordRequest(
            userId: $user->id(),
            password: '666'
        );

        $result = $sut($request);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof ChangePasswordResponse);
        $this->assertTrue($user->id()->equals($result->userId));

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof PasswordChanged);
        $this->assertTrue($user->id()->equals($lastEvent->userId()));

        $userPersisted = $repository->find($result->userId);

        $this->assertNotNull($userPersisted);
        $this->assertTrue($userPersisted instanceof User);
        $this->assertNotEquals($request->password, $userPersisted->password());
    }

    private function createSUT($repository)
    {
        $passwordHasher = new Md5PasswordHasher();

        return new ChangePasswordService($repository, $passwordHasher);
    }
}