<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\User\View;

use App\Security\Application\UnauthorizedActionException;
use App\Security\Application\User\View\ViewUserRequest;
use App\Security\Application\User\View\ViewUserService;
use App\Security\Domain\Model\User\UserId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Application\User\UserAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryMother;
use PHPUnit\Framework\TestCase;

class ViewUserServiceTest extends TestCase
{
    private InMemoryUserRepository $repository;

    private ViewUserService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = UserRepositoryMother::inMemory();
        $this->sut = new ViewUserService($this->repository, UserAuthorizerMother::create());
    }

    public function testReturnsTheUser(): void
    {
        $user = UserMother::fromUsername('ana@acme.test');
        $this->repository->add($user);

        $response = ($this->sut)(new ViewUserRequest($user->id()), UserMother::adminUser());

        $this->assertTrue($response->user->id()->equals($user->id()));
        $this->assertSame('ana@acme.test', $response->user->username());
    }

    public function testFailsWhenTheUserDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new ViewUserRequest(UserId::generate()), UserMother::adminUser());
    }

    public function testAUserCannotViewAnotherUser(): void
    {
        $user = UserMother::fromUsername('ana@acme.test');
        $this->repository->add($user);

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new ViewUserRequest($user->id()), UserMother::registeredUser());
    }
}
