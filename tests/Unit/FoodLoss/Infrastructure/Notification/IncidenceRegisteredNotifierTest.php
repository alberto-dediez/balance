<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Infrastructure\Notification;

use App\FoodLoss\Domain\Model\Incidence\IncidenceId;
use App\FoodLoss\Domain\Model\Incidence\IncidenceRegistered;
use App\FoodLoss\Domain\Model\Incidence\IncidenceRemoved;
use App\FoodLoss\Domain\Model\Incidence\IncidenceType;
use App\FoodLoss\Infrastructure\Notification\IncidenceRegisteredNotifier;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryMother;
use PHPUnit\Framework\TestCase;

class IncidenceRegisteredNotifierTest extends TestCase
{
    private IncidenceRegisteredNotifier $sut;
    private UserRepository $userRepository;
    private SpySystemNotifier $spyNotifier;
    private IncidenceRegistered $event;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->userRepository = UserRepositoryMother::inMemory();
        $this->spyNotifier = new SpySystemNotifier();

        $owner = UserMother::fromUsernameAndRoleAndAccount('owner@test.com', Role::Owner, new AccountId('account-id-test'));
        $this->userRepository->add($owner);

        $this->event = new IncidenceRegistered(
            incidenceId: IncidenceId::generate(),
            name: 'Fallo de maquinaria',
            exceptionOccurredOn: new \DateTimeImmutable('2026-04-08 10:00:00'),
            incidenceType: IncidenceType::MachineryFailure,
            description: 'Description',
            effect: 'Effect',
            propertyId: PropertyId::generate(),
            accountId: new AccountId('account-id-test'),
        );

        $this->sut = $this->createSUT();
    }

    public function testIsSubscribedToIncidenceRegistered(): void
    {
        $this->assertTrue($this->sut->isSubscribedTo($this->event));
    }

    public function testIsNotSubscribedToOtherEvents(): void
    {
        $this->assertFalse($this->sut->isSubscribedTo(new IncidenceRemoved(IncidenceId::generate())));
    }

    public function testNotifiesAccountOwners(): void
    {
        $this->sut->handle($this->event);

        $this->assertCount(1, $this->spyNotifier->notifications);
        $notification = $this->spyNotifier->notifications[0];
        $this->assertSame(NotificationChannel::Internal, $notification->channel);
        $this->assertSame('Nueva incidencia registrada', $notification->subject);
        $this->assertSame('@food_loss/incidences/registered.notification.html.twig', $notification->template);
    }

    public function testNotifiesEachOwnerOfTheSameAccount(): void
    {
        $secondOwner = UserMother::fromUsernameAndRoleAndAccount('owner2@test.com', Role::Owner, new AccountId('account-id-test'));
        $this->userRepository->add($secondOwner);

        $this->sut->handle($this->event);

        $this->assertCount(2, $this->spyNotifier->notifications);
    }

    public function testSkipsRemovedOwners(): void
    {
        $removedOwner = UserMother::fromUsernameAndRoleAndAccount('removed@test.com', Role::Owner, new AccountId('account-id-test'));
        $removedOwner->remove();
        $this->userRepository->add($removedOwner);

        $this->sut->handle($this->event);

        $this->assertCount(1, $this->spyNotifier->notifications);
    }

    public function testIgnoresNonOwnerUsers(): void
    {
        $normalUser = UserMother::fromUsernameAndRoleAndAccount('user@test.com', Role::User, new AccountId('account-id-test'));
        $this->userRepository->add($normalUser);

        $this->sut->handle($this->event);

        $this->assertCount(1, $this->spyNotifier->notifications);
    }

    public function testDoesNothingWhenAccountHasNoOwners(): void
    {
        $emptyRepository = UserRepositoryMother::inMemory();

        $sut = new IncidenceRegisteredNotifier(
            notifier: $this->spyNotifier,
            userRepository: $emptyRepository,
        );

        $sut->handle($this->event);

        $this->assertCount(0, $this->spyNotifier->notifications);
    }

    private function createSUT(): IncidenceRegisteredNotifier
    {
        return new IncidenceRegisteredNotifier(
            notifier: $this->spyNotifier,
            userRepository: $this->userRepository,
        );
    }
}
