<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Infrastructure\Notification;

use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceId;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRepository;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceSignatureCancelled;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceSignatureCompleted;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionId;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRepository;
use App\FoodLoss\Infrastructure\Notification\TrainingAttendanceSignatureCompletedNotifier;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceMother;
use App\Tests\Unit\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRepositoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\TrainingSession\TrainingSessionMother;
use App\Tests\Unit\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRepositoryMother;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryMother;
use PHPUnit\Framework\TestCase;

class TrainingAttendanceSignatureCompletedNotifierTest extends TestCase
{
    private TrainingAttendanceSignatureCompletedNotifier $sut;
    private TrainingAttendanceRepository $trainingAttendanceRepository;
    private TrainingSessionRepository $trainingSessionRepository;
    private EmployeeRepository $employeeRepository;
    private UserRepository $userRepository;
    private SpySystemNotifier $spyNotifier;
    private TrainingAttendanceSignatureCompleted $event;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->trainingAttendanceRepository = TrainingAttendanceRepositoryMother::inMemory();
        $this->trainingSessionRepository = TrainingSessionRepositoryMother::inMemory();
        $this->employeeRepository = EmployeeRepositoryMother::inMemory();
        $this->userRepository = UserRepositoryMother::inMemory();
        $this->spyNotifier = new SpySystemNotifier();

        $trainingSessionId = TrainingSessionId::generate();
        $employeeId = EmployeeId::generate();

        $trainingSession = TrainingSessionMother::fromTitleAndId('Food safety training', (string) $trainingSessionId);
        $this->trainingSessionRepository->add($trainingSession);

        $attendance = TrainingAttendanceMother::inSignatureProcess(
            accountId: 'account-id-test',
            trainingSessionId: $trainingSessionId,
            employeeId: $employeeId,
        );
        $this->trainingAttendanceRepository->add($attendance);

        $owner = UserMother::fromUsernameAndRoleAndAccount('owner@test.com', Role::Owner, new AccountId('account-id-test'));
        $this->userRepository->add($owner);

        $this->event = new TrainingAttendanceSignatureCompleted(
            trainingAttendanceId: $attendance->id(),
            signedFilePath: 'training_attendances/account-id/signed.pdf',
        );

        $this->sut = $this->createSUT();
    }

    public function testIsSubscribedToTrainingAttendanceSignatureCompleted(): void
    {
        $this->assertTrue($this->sut->isSubscribedTo($this->event));
    }

    public function testIsNotSubscribedToOtherEvents(): void
    {
        $this->assertFalse($this->sut->isSubscribedTo(new TrainingAttendanceSignatureCancelled(TrainingAttendanceId::generate())));
    }

    public function testNotifiesAccountOwners(): void
    {
        $this->sut->handle($this->event);

        $this->assertCount(1, $this->spyNotifier->notifications);
        $notification = $this->spyNotifier->notifications[0];
        $this->assertSame(NotificationChannel::Internal, $notification->channel);
        $this->assertSame('Asistencia a formación firmada', $notification->subject);
        $this->assertSame('@food_loss/training-attendance/signature-completed.notification.html.twig', $notification->template);
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

    public function testDoesNothingWhenTrainingAttendanceNotFound(): void
    {
        $event = new TrainingAttendanceSignatureCompleted(
            trainingAttendanceId: TrainingAttendanceId::generate(),
            signedFilePath: 'signed.pdf',
        );

        $this->sut->handle($event);

        $this->assertCount(0, $this->spyNotifier->notifications);
    }

    public function testDoesNothingWhenTrainingSessionNotFound(): void
    {
        $attendance = TrainingAttendanceMother::inSignatureProcess(
            accountId: 'account-id-test',
            trainingSessionId: TrainingSessionId::generate(),
        );
        $this->trainingAttendanceRepository->add($attendance);

        $event = new TrainingAttendanceSignatureCompleted(
            trainingAttendanceId: $attendance->id(),
            signedFilePath: 'signed.pdf',
        );

        $this->sut->handle($event);

        $this->assertCount(0, $this->spyNotifier->notifications);
    }

    public function testDoesNothingWhenAccountHasNoOwners(): void
    {
        $emptyUserRepository = UserRepositoryMother::inMemory();

        $sut = new TrainingAttendanceSignatureCompletedNotifier(
            notifier: $this->spyNotifier,
            trainingAttendanceRepository: $this->trainingAttendanceRepository,
            trainingSessionRepository: $this->trainingSessionRepository,
            employeeRepository: $this->employeeRepository,
            userRepository: $emptyUserRepository,
        );

        $sut->handle($this->event);

        $this->assertCount(0, $this->spyNotifier->notifications);
    }

    private function createSUT(): TrainingAttendanceSignatureCompletedNotifier
    {
        return new TrainingAttendanceSignatureCompletedNotifier(
            notifier: $this->spyNotifier,
            trainingAttendanceRepository: $this->trainingAttendanceRepository,
            trainingSessionRepository: $this->trainingSessionRepository,
            employeeRepository: $this->employeeRepository,
            userRepository: $this->userRepository,
        );
    }
}
