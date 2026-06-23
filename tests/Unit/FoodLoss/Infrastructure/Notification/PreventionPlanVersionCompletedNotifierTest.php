<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Infrastructure\Notification;

use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanId;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanVersionCompleted;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanVersionValidated;
use App\FoodLoss\Infrastructure\Notification\PreventionPlanVersionCompletedNotifier;
use App\Notifications\Application\Notification\Notify\SystemNotification;
use App\Notifications\Application\Notification\Notify\SystemNotifier;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanMother;
use App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryMother;
use PHPUnit\Framework\TestCase;

class PreventionPlanVersionCompletedNotifierTest extends TestCase
{
    private PreventionPlanVersionCompletedNotifier $sut;
    private PreventionPlanRepository $preventionPlanRepository;
    private UserRepository $userRepository;
    private SpySystemNotifier $spyNotifier;
    private PreventionPlanVersionCompleted $event;
    private User $creator;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->preventionPlanRepository = PreventionPlanRepositoryMother::inMemory();
        $this->userRepository = UserRepositoryMother::inMemory();
        $this->spyNotifier = new SpySystemNotifier();

        $this->creator = UserMother::from(userId: new UserId('user-id'));
        $this->userRepository->add($this->creator);

        $plan = PreventionPlanMother::registeredPreventionPlan();
        $this->preventionPlanRepository->add($plan);

        $this->event = new PreventionPlanVersionCompleted(
            preventionPlanId: $plan->id(),
            versionNumber: $plan->currentVersion(),
        );

        $this->sut = $this->createSUT();
    }

    public function testIsSubscribedToPreventionPlanVersionCompleted(): void
    {
        $this->assertTrue($this->sut->isSubscribedTo($this->event));
    }

    public function testIsNotSubscribedToOtherEvents(): void
    {
        $this->assertFalse($this->sut->isSubscribedTo(new PreventionPlanVersionValidated(
            PreventionPlanId::generate(),
            1,
        )));
    }

    public function testNotifiesVersionCreatorOnBothChannels(): void
    {
        $this->sut->handle($this->event);

        $this->assertCount(2, $this->spyNotifier->notifications);
        $this->assertTrue($this->creator->id()->equals($this->spyNotifier->notifications[0]->recipientId));
        $this->assertSame(NotificationChannel::Internal, $this->spyNotifier->notifications[0]->channel);
        $this->assertSame(NotificationChannel::Email, $this->spyNotifier->notifications[1]->channel);
        $this->assertSame('Plan de prevención publicado', $this->spyNotifier->notifications[0]->subject);
        $this->assertSame('@food_loss/prevention-plan/version-completed.notification.html.twig', $this->spyNotifier->notifications[0]->template);
        $this->assertSame('@food_loss/prevention-plan/version-completed.email.html.twig', $this->spyNotifier->notifications[1]->template);
    }

    public function testDoesNothingWhenPreventionPlanNotFound(): void
    {
        $unknownEvent = new PreventionPlanVersionCompleted(
            preventionPlanId: PreventionPlanId::generate(),
            versionNumber: 1,
        );

        $this->sut->handle($unknownEvent);

        $this->assertCount(0, $this->spyNotifier->notifications);
    }

    public function testDoesNothingWhenVersionNotFound(): void
    {
        $plan = PreventionPlanMother::registeredPreventionPlan();
        $this->preventionPlanRepository->add($plan);

        $eventForMissingVersion = new PreventionPlanVersionCompleted(
            preventionPlanId: $plan->id(),
            versionNumber: 99,
        );

        $this->sut->handle($eventForMissingVersion);

        $this->assertCount(0, $this->spyNotifier->notifications);
    }

    public function testDoesNothingWhenCreatorUserNotFound(): void
    {
        $emptyUserRepository = UserRepositoryMother::inMemory();

        $sut = new PreventionPlanVersionCompletedNotifier(
            notifier: $this->spyNotifier,
            preventionPlanRepository: $this->preventionPlanRepository,
            userRepository: $emptyUserRepository,
        );

        $sut->handle($this->event);

        $this->assertCount(0, $this->spyNotifier->notifications);
    }

    private function createSUT(): PreventionPlanVersionCompletedNotifier
    {
        return new PreventionPlanVersionCompletedNotifier(
            notifier: $this->spyNotifier,
            preventionPlanRepository: $this->preventionPlanRepository,
            userRepository: $this->userRepository,
        );
    }
}

class SpySystemNotifier implements SystemNotifier
{
    /** @var array<int, SystemNotification> */
    public array $notifications = [];

    public function notify(SystemNotification $notification): void
    {
        $this->notifications[] = $notification;
    }
}
