<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Infrastructure\Notification;

use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignmentId;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignmentRemoved;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Notification\DistributorAssignmentRemovedNotifier;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Notifications\Application\Notification\Notify\SpySystemNotifier;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class DistributorAssignmentRemovedNotifierTest extends TestCase
{
    private Account $distributor;

    private Account $hotel;

    private InMemoryAccountRepository $accountRepository;

    private InMemoryUserRepository $userRepository;

    private InMemoryPropertyRepository $propertyRepository;

    private SpySystemNotifier $notifier;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->distributor = AccountMother::distributorAccountFromNameAndId('Greenhost', 'distributor');
        $this->hotel = AccountMother::fromNameAndId('Hotel A', 'hotel');

        $this->accountRepository = new InMemoryAccountRepository();
        $this->accountRepository->add($this->distributor);
        $this->accountRepository->add($this->hotel);

        $this->userRepository = new InMemoryUserRepository();
        $this->propertyRepository = new InMemoryPropertyRepository();
        $this->notifier = new SpySystemNotifier();
    }

    public function testNotifiesEveryOwnerOfTheDistributorWhenAnAccountIsRemoved(): void
    {
        $this->userRepository->add(UserMother::fromUsernameAndRoleAndAccount('owner1', Role::Owner, $this->distributor->id()));
        $this->userRepository->add(UserMother::fromUsernameAndRoleAndAccount('owner2', Role::Owner, $this->distributor->id()));
        $this->userRepository->add(UserMother::fromUsernameAndRoleAndAccount('user', Role::User, $this->distributor->id()));
        $this->userRepository->add(UserMother::fromUsernameAndRoleAndAccount('hotel-owner', Role::Owner, $this->hotel->id()));

        $this->createSUT()->handle($this->event(propertyId: null, notify: true));

        $notifications = $this->notifier->notifications();

        $this->assertCount(2, $notifications);
        $this->assertSame(NotificationChannel::Internal, $notifications[0]->channel);
        $this->assertSame('Hotel A', $notifications[0]->params['accountName']);
        $this->assertSame('Greenhost', $notifications[0]->params['distributorName']);
        $this->assertNull($notifications[0]->params['propertyName']);
        $this->assertNotNull($notifications[0]->accountId);
        $this->assertTrue($this->distributor->id()->equals($notifications[0]->accountId));
    }

    public function testNotifiesWithThePropertyNameWhenAPropertyIsRemoved(): void
    {
        $property = PropertyMother::from(PropertyId::generate(), 'Hotel Playa', $this->hotel->id());
        $this->propertyRepository->add($property);
        $this->userRepository->add(UserMother::fromUsernameAndRoleAndAccount('owner1', Role::Owner, $this->distributor->id()));

        $this->createSUT()->handle($this->event(propertyId: $property->id(), notify: true));

        $notifications = $this->notifier->notifications();

        $this->assertCount(1, $notifications);
        $this->assertSame('Hotel Playa', $notifications[0]->params['propertyName']);
        $this->assertSame('Hotel A', $notifications[0]->params['accountName']);
    }

    public function testDoesNotNotifyWhenTheFlagIsOff(): void
    {
        $this->userRepository->add(UserMother::fromUsernameAndRoleAndAccount('owner1', Role::Owner, $this->distributor->id()));

        $this->createSUT()->handle($this->event(propertyId: null, notify: false));

        $this->assertSame([], $this->notifier->notifications());
    }

    public function testDoesNotNotifyWithoutOwners(): void
    {
        $this->userRepository->add(UserMother::fromUsernameAndRoleAndAccount('user', Role::User, $this->distributor->id()));

        $this->createSUT()->handle($this->event(propertyId: null, notify: true));

        $this->assertSame([], $this->notifier->notifications());
    }

    private function event(?PropertyId $propertyId, bool $notify): DistributorAssignmentRemoved
    {
        return new DistributorAssignmentRemoved(
            distributorAssignmentId: DistributorAssignmentId::generate(),
            distributorAccountId: $this->distributor->id(),
            accountId: $propertyId instanceof PropertyId ? null : $this->hotel->id(),
            propertyId: $propertyId,
            removedBy: UserId::generate(),
            notifyDistributor: $notify,
        );
    }

    private function createSUT(): DistributorAssignmentRemovedNotifier
    {
        return new DistributorAssignmentRemovedNotifier(
            $this->notifier,
            $this->accountRepository,
            $this->userRepository,
            $this->propertyRepository,
        );
    }
}
