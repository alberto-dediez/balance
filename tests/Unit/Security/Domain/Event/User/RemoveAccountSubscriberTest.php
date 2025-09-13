<?php

namespace App\Tests\Unit\Security\Domain\Event\User;

use App\Security\Application\User\Remove\RemoveUserService;
use App\Security\Domain\Event\User\RemoveAccountSubscriber;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRemoved;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRemoved;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use PHPUnit\Framework\TestCase;

class RemoveAccountSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function isSubscribedToReturnsTrueIfAccountRemovedEventIsPassed()
    {
        $repository = new InMemoryUserRepository();

        $sut = $this->createSUT($repository);

        $event = new AccountRemoved(
            AccountId::generate()
        );

        $result = $sut->isSubscribedTo($event);

        $this->assertTrue($result);
    }

    public function isSubscribedToReturnsFalseIfNotAccountRemovedEventIsPassed()
    {
        $repository = new InMemoryUserRepository();

        $sut = $this->createSUT($repository);

        $event = new UserRemoved(
            UserId::generate()
        );

        $result = $sut->isSubscribedTo($event);

        $this->assertFalse($result);
    }

    public function testHandleCallsRemoveUserService()
    {
        $repository = new InMemoryUserRepository();

        $account = AccountMother::fromName('MyCompany');
        $otherAccount = AccountMother::fromName('OtherCompany');

        $user = UserMother::fromUsernameAndAccount('user-1', $account);
        $repository->add($user);

        $user = UserMother::fromUsernameAndAccount('user-2', $account);
        $repository->add($user);

        $user = UserMother::fromUsernameAndAccount('user-3', $account);
        $repository->add($user);

        $user = UserMother::fromUsernameAndAccount('user-4', $otherAccount);
        $repository->add($user);

        $user = UserMother::fromUsername('user-5');
        $repository->add($user);

        $event = new AccountRemoved(
            $account->id()
        );

        $sut = $this->createSUT(
            repository: $repository,
            usersToRemoved: 3
        );
        $sut->handle($event);
    }

    private function createSUT($repository, int $usersToRemoved = 0)
    {
        $removeUser = $this->createMock(RemoveUserService::class);
        $removeUser->expects($this->exactly($usersToRemoved))->method('__invoke');

        /** @var RemoveUserService $removeUser */

        return new RemoveAccountSubscriber(
            $repository,
            $removeUser,
            new FakeLoggedUser(UserMother::adminUser())
        );
    }
}
