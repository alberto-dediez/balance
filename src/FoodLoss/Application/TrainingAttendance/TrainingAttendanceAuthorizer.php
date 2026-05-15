<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\TrainingAttendance;

use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendance;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class TrainingAttendanceAuthorizer extends AbstractAuthorizer
{
    protected function canRegister(mixed $subject, User $user): bool
    {
        return $this->canEdit($subject, $user);
    }

    protected function canView(mixed $subject, User $user): bool
    {
        return $this->canEdit($subject, $user);
    }

    protected function canList(mixed $subject, User $user): bool
    {
        return $this->canEdit($subject, $user);
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        assert($subject instanceof TrainingAttendance);

        if (!$user->hasRole(Role::Owner) || !$user->accountId() instanceof AccountId) {
            return false;
        }

        return (bool) $subject->accountId()?->equals($user->accountId());
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return $this->canEdit($subject, $user);
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof TrainingAttendance;
    }
}
