<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityAssessment;

use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessment;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class MaterialityAssessmentAuthorizer extends AbstractAuthorizer
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
        $currentAccountId = $this->currentAccountId($user);

        if (!$this->userHasRole($user, Role::Owner) || !$currentAccountId instanceof AccountId) {
            return false;
        }

        if (!$subject instanceof MaterialityAssessment) {
            return false;
        }

        $subjectAccountId = $subject->accountId();

        return null !== $subjectAccountId && $currentAccountId->equals($subjectAccountId);
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return $this->canEdit($subject, $user)
            || $this->userHasRole($user, Role::Admin)
            || $this->userHasRole($user, Role::SuperAdmin);
    }

    protected function canImport(mixed $subject, User $user): bool
    {
        return false;
    }

    protected function canExport(mixed $subject, User $user): bool
    {
        return false;
    }

    protected function canAutocomplete(mixed $subject, User $user): bool
    {
        return false;
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof MaterialityAssessment;
    }
}
