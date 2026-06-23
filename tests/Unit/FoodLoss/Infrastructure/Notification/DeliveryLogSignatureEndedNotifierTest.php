<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Infrastructure\Notification;

use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogId;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepository;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogSignatureCancelled;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogSignatureEnded;
use App\FoodLoss\Infrastructure\Notification\DeliveryLogSignatureEndedNotifier;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogMother;
use App\Tests\Unit\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryMother;
use PHPUnit\Framework\TestCase;

class DeliveryLogSignatureEndedNotifierTest extends TestCase
{
    private DeliveryLogSignatureEndedNotifier $sut;
    private DeliveryLogRepository $deliveryLogRepository;
    private UserRepository $userRepository;
    private SpySystemNotifier $spyNotifier;
    private DeliveryLogSignatureEnded $event;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->deliveryLogRepository = DeliveryLogRepositoryMother::inMemory();
        $this->userRepository = UserRepositoryMother::inMemory();
        $this->spyNotifier = new SpySystemNotifier();

        $deliveryLog = DeliveryLogMother::registeredDeliveryLog();
        $this->deliveryLogRepository->add($deliveryLog);

        $owner = UserMother::fromUsernameAndRoleAndAccount('owner@test.com', Role::Owner, new AccountId('account-id-test'));
        $this->userRepository->add($owner);

        $this->event = new DeliveryLogSignatureEnded(
            deliveryLogId: $deliveryLog->id(),
        );

        $this->sut = $this->createSUT();
    }

    public function testIsSubscribedToDeliveryLogSignatureEnded(): void
    {
        $this->assertTrue($this->sut->isSubscribedTo($this->event));
    }

    public function testIsNotSubscribedToOtherEvents(): void
    {
        $this->assertFalse($this->sut->isSubscribedTo(
            new DeliveryLogSignatureCancelled(DeliveryLogId::generate()),
        ));
    }

    public function testNotifiesAccountOwners(): void
    {
        $this->sut->handle($this->event);

        $this->assertCount(1, $this->spyNotifier->notifications);
        $notification = $this->spyNotifier->notifications[0];
        $this->assertSame(NotificationChannel::Internal, $notification->channel);
        $this->assertSame('Entrega confirmada por la organización destinataria', $notification->subject);
        $this->assertSame('@food_loss/delivery-logs/signature-ended.notification.html.twig', $notification->template);
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

    public function testDoesNothingWhenDeliveryLogNotFound(): void
    {
        $event = new DeliveryLogSignatureEnded(
            deliveryLogId: DeliveryLogId::generate(),
        );

        $this->sut->handle($event);

        $this->assertCount(0, $this->spyNotifier->notifications);
    }

    public function testDoesNothingWhenAccountHasNoOwners(): void
    {
        $emptyUserRepository = UserRepositoryMother::inMemory();

        $sut = new DeliveryLogSignatureEndedNotifier(
            notifier: $this->spyNotifier,
            deliveryLogRepository: $this->deliveryLogRepository,
            userRepository: $emptyUserRepository,
        );

        $sut->handle($this->event);

        $this->assertCount(0, $this->spyNotifier->notifications);
    }

    private function createSUT(): DeliveryLogSignatureEndedNotifier
    {
        return new DeliveryLogSignatureEndedNotifier(
            notifier: $this->spyNotifier,
            deliveryLogRepository: $this->deliveryLogRepository,
            userRepository: $this->userRepository,
        );
    }
}
