<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\TrainingSession;

use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceFilter;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRepository;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSession;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class TrainingSessionAuthorizer extends AbstractAuthorizer
{
    public function __construct(private readonly TrainingAttendanceRepository $trainingAttendanceRepository) {}

    protected function canRegister(mixed $subject, User $user): bool
    {
        return $this->canEdit($subject, $user);
    }

    protected function canView(mixed $subject, User $user): bool
    {
        return $this->canList($subject, $user);
    }

    protected function canList(mixed $subject, User $user): bool
    {
        assert($subject instanceof TrainingSession);

        if (!$user->accountId() instanceof AccountId) {
            return false;
        }

        $subjectAccountId = $subject->accountId();

        if (null === $subjectAccountId) {
            return false;
        }

        return ($user->hasRole(Role::Owner) || $user->hasRole(Role::User))
            && $user->accountId()->equals($subjectAccountId);
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        assert($subject instanceof TrainingSession);

        if (!$user->hasRole(Role::Owner) || !$user->accountId() instanceof AccountId) {
            return false;
        }

        $subjectAccountId = $subject->accountId();

        if (null === $subjectAccountId || !$user->accountId()->equals($subjectAccountId)) {
            return false;
        }

        $attendanceCount = $this->trainingAttendanceRepository->totalResultsByFilter(
            new TrainingAttendanceFilter(trainingSessionId: $subject->id()),
        );

        return 0 === $attendanceCount;
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return $this->canEdit($subject, $user);
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof TrainingSession;
    }
}
