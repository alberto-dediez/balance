<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Infrastructure\Notification;

use App\FoodLoss\Domain\Model\Surplus\Surplus;
use App\FoodLoss\Domain\Model\Surplus\SurplusEliminationAssessed;
use App\FoodLoss\Domain\Model\Surplus\SurplusEliminationUnassessed;
use App\FoodLoss\Domain\Model\Surplus\SurplusId;
use App\FoodLoss\Domain\Model\Surplus\SurplusRepository;
use App\FoodLoss\Infrastructure\Notification\SurplusEliminationAssessedNotifier;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\Surplus\SurplusMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Surplus\SurplusRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryMother;
use PHPUnit\Framework\TestCase;

class SurplusEliminationAssessedNotifierTest extends TestCase
{
    private SurplusEliminationAssessedNotifier $sut;
    private SurplusRepository $surplusRepository;
    private UserRepository $userRepository;
    private SpySystemNotifier $spyNotifier;
    private SurplusEliminationAssessed $event;
    private Surplus $surplus;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->surplusRepository = SurplusRepositoryMother::inMemory();
        $this->userRepository = UserRepositoryMother::inMemory();
        $this->spyNotifier = new SpySystemNotifier();

        $this->surplus = SurplusMother::from(
            id: (string) SurplusId::generate(),
            accountId: 'account-id-test',
        );
        $this->surplusRepository->add($this->surplus);

        $owner = UserMother::fromUsernameAndRoleAndAccount('owner@test.com', Role::Owner, new AccountId('account-id-test'));
        $this->userRepository->add($owner);

        $this->event = new SurplusEliminationAssessed(
            surplusId: $this->surplus->id(),
            canBeEliminated: true,
        );

        $this->sut = $this->createSUT();
    }

    public function testIsSubscribedToSurplusEliminationAssessed(): void
    {
        $this->assertTrue($this->sut->isSubscribedTo($this->event));
    }

    public function testIsNotSubscribedToOtherEvents(): void
    {
        $this->assertFalse($this->sut->isSubscribedTo(new SurplusEliminationUnassessed(SurplusId::generate())));
    }

    public function testNotifiesAccountOwnersWhenEliminationAssigned(): void
    {
        $this->sut->handle($this->event);

        $this->assertCount(1, $this->spyNotifier->notifications);
        $notification = $this->spyNotifier->notifications[0];
        $this->assertSame(NotificationChannel::Internal, $notification->channel);
        $this->assertSame('Excedente asignado a eliminación', $notification->subject);
        $this->assertSame('@food_loss/surpluses/elimination-assessed.notification.html.twig', $notification->template);
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

    public function testDoesNothingWhenCanBeEliminatedIsFalse(): void
    {
        $event = new SurplusEliminationAssessed(
            surplusId: $this->surplus->id(),
            canBeEliminated: false,
        );

        $this->sut->handle($event);

        $this->assertCount(0, $this->spyNotifier->notifications);
    }

    public function testDoesNothingWhenSurplusNotFound(): void
    {
        $event = new SurplusEliminationAssessed(
            surplusId: SurplusId::generate(),
            canBeEliminated: true,
        );

        $this->sut->handle($event);

        $this->assertCount(0, $this->spyNotifier->notifications);
    }

    public function testDoesNothingWhenAccountHasNoOwners(): void
    {
        $emptyUserRepository = UserRepositoryMother::inMemory();

        $sut = new SurplusEliminationAssessedNotifier(
            notifier: $this->spyNotifier,
            surplusRepository: $this->surplusRepository,
            userRepository: $emptyUserRepository,
        );

        $sut->handle($this->event);

        $this->assertCount(0, $this->spyNotifier->notifications);
    }

    private function createSUT(): SurplusEliminationAssessedNotifier
    {
        return new SurplusEliminationAssessedNotifier(
            notifier: $this->spyNotifier,
            surplusRepository: $this->surplusRepository,
            userRepository: $this->userRepository,
        );
    }
}
