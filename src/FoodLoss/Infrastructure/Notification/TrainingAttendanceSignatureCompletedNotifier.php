<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Notification;

use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendance;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRepository;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceSignatureCompleted;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSession;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionId;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionRepository;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\Notifications\Application\Notification\Notify\SystemNotification;
use App\Notifications\Application\Notification\Notify\SystemNotifier;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;
use Symfony\Component\Translation\TranslatableMessage;

class TrainingAttendanceSignatureCompletedNotifier implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private readonly SystemNotifier $notifier,
        private readonly TrainingAttendanceRepository $trainingAttendanceRepository,
        private readonly TrainingSessionRepository $trainingSessionRepository,
        private readonly EmployeeRepository $employeeRepository,
        private readonly UserRepository $userRepository,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof TrainingAttendanceSignatureCompleted);

        $this->handleTrainingAttendanceSignatureCompleted($event);
    }

    private function handleTrainingAttendanceSignatureCompleted(TrainingAttendanceSignatureCompleted $event): void
    {
        $trainingAttendance = $this->trainingAttendanceRepository->find($event->trainingAttendanceId());

        if (!$trainingAttendance instanceof TrainingAttendance) {
            return;
        }

        $accountId = $trainingAttendance->accountId();

        if (!$accountId instanceof AccountId) {
            return;
        }

        $trainingSessionId = $trainingAttendance->trainingSessionId();

        if (!$trainingSessionId instanceof TrainingSessionId) {
            return;
        }

        $trainingSession = $this->trainingSessionRepository->find($trainingSessionId);

        if (!$trainingSession instanceof TrainingSession) {
            return;
        }

        $employee = null;
        $employeeId = $trainingAttendance->employeeId();

        if ($employeeId instanceof EmployeeId) {
            $employee = $this->employeeRepository->find($employeeId);
        }

        $subject = (string) new TranslatableMessage('Asistencia a formación firmada');
        $params = [
            'trainingSession' => $trainingSession,
            'employee' => $employee,
        ];

        $owners = array_filter(
            $this->userRepository->findByAccountId($accountId),
            static fn(User $user): bool => $user->hasRole(Role::Owner) && !$user->isRemoved(),
        );

        foreach ($owners as $owner) {
            $this->notifier->notify(new SystemNotification(
                recipientId: $owner->id(),
                channel: NotificationChannel::Internal,
                subject: $subject,
                template: '@food_loss/training-attendance/signature-completed.notification.html.twig',
                params: $params,
                accountId: $owner->accountId(),
            ));
        }
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof TrainingAttendanceSignatureCompleted;
    }
}
