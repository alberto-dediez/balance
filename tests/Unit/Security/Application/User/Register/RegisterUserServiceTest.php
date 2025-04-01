<?php

namespace App\Tests\Unit\Security\Application\User\Register;

use App\Security\Application\UnauthorizedActionException;
use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Security\Domain\Model\User\UserRegistered;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Security\Application\User\Register\RegisterUserRequest;
use App\Security\Application\User\Register\RegisterUserService;
use App\Security\Application\User\Register\RegisterUserResponse;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Security\Infrastructure\Symfony\Security\Md5PasswordHasher;
use App\Security\Application\User\Register\DuplicateUsernameException;
use App\Security\Infrastructure\Repository\User\InMemoryUserRepository;
use App\Tests\Unit\Security\Application\User\UserAuthorizerMother;

class RegisterUserServiceTest extends TestCase
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

        $sut = $this->createSUT($repository);

        $request = new RegisterUserRequest(
            username: 'an@email.com',
            password: '666'
        );

        $performBy = UserMother::adminUser();

        $result = $sut($request, $performBy);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof RegisterUserResponse);
        $this->assertNotEmpty((string) $result->userId);
        $this->assertEquals(36, strlen((string) $result->userId));

        $userPersisted = $repository->find($result->userId);

        $this->assertNotNull($userPersisted);
        $this->assertTrue($userPersisted instanceof User);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof UserRegistered);
        $this->assertEquals('an@email.com', $lastEvent->username());
        $this->assertTrue($userPersisted->id()->equals($lastEvent->userId()));
    }

    public function testPasswordIsEncoded()
    {
        $repository = new InMemoryUserRepository();

        $sut = $this->createSUT($repository);

        $request = new RegisterUserRequest(
            username: 'an@email.com',
            password: '666'
        );

        $performBy = UserMother::adminUser();

        $result = $sut($request, $performBy);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof RegisterUserResponse);
        $this->assertNotEmpty((string) $result->userId);
        $this->assertEquals(36, strlen((string) $result->userId));

        $userPersisted = $repository->find($result->userId);

        $this->assertNotNull($userPersisted);
        $this->assertTrue($userPersisted instanceof User);
        $this->assertNotEquals($request->password, $userPersisted->password());
    }

    public function testUsernameIsUnique()
    {
        $this->expectException(DuplicateUsernameException::class);

        $repository = new InMemoryUserRepository();

        $sut = $this->createSUT($repository);

        $performBy = UserMother::adminUser();

        $request1 = new RegisterUserRequest(
            username: 'an@email.com',
            password: '666'
        );

        $result = $sut($request1, $performBy);

        $this->assertNotNull($result);

        $request2 = new RegisterUserRequest(
            username: 'an@email.com',
            password: '777'
        );

        $sut($request2, $performBy);
    }

    public function testUnauthorizedAccess()
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = new InMemoryUserRepository();

        $sut = $this->createSUT($repository);

        $performBy = UserMother::ownerUser();

        $request1 = new RegisterUserRequest(
            username: 'an@email.com',
            password: '666'
        );

        $sut($request1, $performBy);
    }

    private function createSUT($repository)
    {
        $passwordHasher = new Md5PasswordHasher();

        return new RegisterUserService($repository, $passwordHasher, UserAuthorizerMother::create());
    }
}