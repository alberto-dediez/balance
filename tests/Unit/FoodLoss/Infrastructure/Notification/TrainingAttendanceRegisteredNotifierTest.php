<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Infrastructure\Notification;

use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceId;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRegistered;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRemoved;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionId;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRepository;
use App\FoodLoss\Infrastructure\Notification\TrainingAttendanceRegisteredNotifier;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\TrainingSession\TrainingSessionMother;
use App\Tests\Unit\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRepositoryMother;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeMother;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryMother;
use PHPUnit\Framework\TestCase;

class TrainingAttendanceRegisteredNotifierTest extends TestCase
{
    private TrainingAttendanceRegisteredNotifier $sut;
    private EmployeeRepository $employeeRepository;
    private UserRepository $userRepository;
    private TrainingSessionRepository $trainingSessionRepository;
    private SpySystemNotifier $spyNotifier;
    private TrainingAttendanceRegistered $event;
    private User $employeeUser;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->employeeRepository = EmployeeRepositoryMother::inMemory();
        $this->userRepository = UserRepositoryMother::inMemory();
        $this->trainingSessionRepository = TrainingSessionRepositoryMother::inMemory();
        $this->spyNotifier = new SpySystemNotifier();

        $employeeId = (string) EmployeeId::generate();
        $userId = (string) UserId::generate();
        $trainingSessionId = (string) TrainingSessionId::generate();

        $employee = EmployeeMother::withUserId($employeeId, $userId);
        $this->employeeRepository->add($employee);

        $this->employeeUser = UserMother::from(userId: new UserId($userId));
        $this->userRepository->add($this->employeeUser);

        $trainingSession = TrainingSessionMother::fromTitleAndId('Food safety training', $trainingSessionId);
        $this->trainingSessionRepository->add($trainingSession);

        $this->event = new TrainingAttendanceRegistered(
            trainingAttendanceId: TrainingAttendanceId::generate(),
            trainingSessionId: new TrainingSessionId($trainingSessionId),
            employeeId: new EmployeeId($employeeId),
            accountId: new AccountId('account-id-test'),
            attendedAt: new \DateTimeImmutable('2024-03-15'),
            filePath: 'training_attendances/account-id/sheet.pdf',
        );

        $this->sut = $this->createSUT();
    }

    public function testIsSubscribedToTrainingAttendanceRegistered(): void
    {
        $this->assertTrue($this->sut->isSubscribedTo($this->event));
    }

    public function testIsNotSubscribedToOtherEvents(): void
    {
        $this->assertFalse($this->sut->isSubscribedTo(new TrainingAttendanceRemoved(TrainingAttendanceId::generate())));
    }

    public function testNotifiesEmployeeUser(): void
    {
        $this->sut->handle($this->event);

        $this->assertCount(1, $this->spyNotifier->notifications);
        $notification = $this->spyNotifier->notifications[0];
        $this->assertTrue($this->employeeUser->id()->equals($notification->recipientId));
        $this->assertSame(NotificationChannel::Internal, $notification->channel);
        $this->assertSame('Se ha registrado tu asistencia a la formación', $notification->subject);
        $this->assertSame('@food_loss/training-attendance/registered.notification.html.twig', $notification->template);
    }

    public function testDoesNothingWhenEmployeeNotFound(): void
    {
        $event = new TrainingAttendanceRegistered(
            trainingAttendanceId: TrainingAttendanceId::generate(),
            trainingSessionId: new TrainingSessionId((string) TrainingSessionId::generate()),
            employeeId: EmployeeId::generate(),
            accountId: new AccountId('account-id-test'),
            attendedAt: new \DateTimeImmutable('2024-03-15'),
            filePath: 'sheet.pdf',
        );

        $this->sut->handle($event);

        $this->assertCount(0, $this->spyNotifier->notifications);
    }

    public function testDoesNothingWhenEmployeeHasNoUserId(): void
    {
        $employeeWithoutUser = EmployeeMother::fromNameAndEmail('No User', 'nouser@example.com');
        $this->employeeRepository->add($employeeWithoutUser);

        $event = new TrainingAttendanceRegistered(
            trainingAttendanceId: TrainingAttendanceId::generate(),
            trainingSessionId: new TrainingSessionId((string) TrainingSessionId::generate()),
            employeeId: $employeeWithoutUser->id(),
            accountId: new AccountId('account-id-test'),
            attendedAt: new \DateTimeImmutable('2024-03-15'),
            filePath: 'sheet.pdf',
        );

        $this->sut->handle($event);

        $this->assertCount(0, $this->spyNotifier->notifications);
    }

    public function testDoesNothingWhenUserNotFound(): void
    {
        $employeeId = (string) EmployeeId::generate();
        $orphanUserId = (string) UserId::generate();

        $employee = EmployeeMother::withUserId($employeeId, $orphanUserId);
        $this->employeeRepository->add($employee);

        $event = new TrainingAttendanceRegistered(
            trainingAttendanceId: TrainingAttendanceId::generate(),
            trainingSessionId: new TrainingSessionId((string) TrainingSessionId::generate()),
            employeeId: new EmployeeId($employeeId),
            accountId: new AccountId('account-id-test'),
            attendedAt: new \DateTimeImmutable('2024-03-15'),
            filePath: 'sheet.pdf',
        );

        $this->sut->handle($event);

        $this->assertCount(0, $this->spyNotifier->notifications);
    }

    public function testDoesNothingWhenTrainingSessionNotFound(): void
    {
        $employeeId = (string) EmployeeId::generate();
        $userId = (string) UserId::generate();

        $employee = EmployeeMother::withUserId($employeeId, $userId);
        $this->employeeRepository->add($employee);

        $user = UserMother::from(userId: new UserId($userId), username: 'session-not-found@test.com');
        $this->userRepository->add($user);

        $event = new TrainingAttendanceRegistered(
            trainingAttendanceId: TrainingAttendanceId::generate(),
            trainingSessionId: TrainingSessionId::generate(),
            employeeId: new EmployeeId($employeeId),
            accountId: new AccountId('account-id-test'),
            attendedAt: new \DateTimeImmutable('2024-03-15'),
            filePath: 'sheet.pdf',
        );

        $this->sut->handle($event);

        $this->assertCount(0, $this->spyNotifier->notifications);
    }

    private function createSUT(): TrainingAttendanceRegisteredNotifier
    {
        return new TrainingAttendanceRegisteredNotifier(
            notifier: $this->spyNotifier,
            employeeRepository: $this->employeeRepository,
            userRepository: $this->userRepository,
            trainingSessionRepository: $this->trainingSessionRepository,
        );
    }
}
