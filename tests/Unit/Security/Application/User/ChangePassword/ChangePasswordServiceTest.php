<?php

namespace App\Tests\Unit\Security\Application\User\ChangePassword;

use App\Security\Application\UnauthorizedActionException;
use App\Security\Application\User\ChangePassword\ChangePasswordRequest;
use App\Security\Application\User\ChangePassword\ChangePasswordResponse;
use App\Security\Application\User\ChangePassword\ChangePasswordService;
use App\Security\Domain\Model\User\PasswordChanged;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Security\Infrastructure\Symfony\Security\Md5PasswordHasher;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Application\User\UserAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

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

        $result = $sut($request, $user);

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

    public function testUnauthorizedAccess()
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = new InMemoryUserRepository();

        $user = UserMother::random();
        $otherUser = UserMother::ownerUser();

        $repository->add($user);

        $sut = $this->createSUT($repository);

        $request = new ChangePasswordRequest(
            userId: $user->id(),
            password: '666'
        );

        $sut($request, $otherUser);
    }

    public function testIsHandle()
    {
        $repository = new InMemoryUserRepository();

        $sut = $this->createSUT($repository);

        $user = UserMother::random();

        $result = $sut->isHandled(new ChangePasswordRequest(
            userId: $user->id(),
            password: '333'
        ));

        $this->assertTrue($result);
    }

    private function createSUT($repository)
    {
        $passwordHasher = new Md5PasswordHasher();

        $authorizer = UserAuthorizerMother::create();

        return new ChangePasswordService($repository, $passwordHasher, $authorizer);
    }
}
