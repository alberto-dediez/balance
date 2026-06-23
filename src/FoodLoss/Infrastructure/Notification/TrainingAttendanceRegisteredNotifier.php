<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Notification;

use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRegistered;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSession;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRepository;
use App\HumanResources\Domain\Model\Employee\Employee;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\Notifications\Application\Notification\Notify\SystemNotification;
use App\Notifications\Application\Notification\Notify\SystemNotifier;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;
use Symfony\Component\Translation\TranslatableMessage;

class TrainingAttendanceRegisteredNotifier implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private readonly SystemNotifier $notifier,
        private readonly EmployeeRepository $employeeRepository,
        private readonly UserRepository $userRepository,
        private readonly TrainingSessionRepository $trainingSessionRepository,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof TrainingAttendanceRegistered);

        $this->handleTrainingAttendanceRegistered($event);
    }

    private function handleTrainingAttendanceRegistered(TrainingAttendanceRegistered $event): void
    {
        $employee = $this->employeeRepository->find($event->employeeId());

        if (!$employee instanceof Employee) {
            return;
        }

        $userId = $employee->userId();

        if (null === $userId) {
            return;
        }

        $user = $this->userRepository->find($userId);

        if (!$user instanceof User) {
            return;
        }

        $trainingSession = $this->trainingSessionRepository->find($event->trainingSessionId());

        if (!$trainingSession instanceof TrainingSession) {
            return;
        }

        $subject = (string) new TranslatableMessage('Se ha registrado tu asistencia a la formación');
        $params = [
            'trainingSession' => $trainingSession,
            'employee' => $employee,
            'attendedAt' => $event->attendedAt(),
        ];

        $this->notifier->notify(new SystemNotification(
            recipientId: $user->id(),
            channel: NotificationChannel::Internal,
            subject: $subject,
            template: '@food_loss/training-attendance/registered.notification.html.twig',
            params: $params,
            accountId: $user->accountId(),
        ));
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof TrainingAttendanceRegistered;
    }
}
