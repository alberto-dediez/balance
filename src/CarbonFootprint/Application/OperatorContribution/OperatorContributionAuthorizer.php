<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OperatorContribution;

use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContribution;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class OperatorContributionAuthorizer extends AbstractAuthorizer
{
    protected function canRegister(mixed $subject, User $user): bool
    {
        return $this->canList($subject, $user);
    }

    protected function canList(mixed $subject, User $user): bool
    {
        if (!$subject instanceof CompanyCarbonFootprint) {
            return false;
        }

        return $this->isOwnerOf($subject->accountId(), $user);
    }

    protected function canView(mixed $subject, User $user): bool
    {
        return $this->canEdit($subject, $user);
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        if (!$subject instanceof OperatorContribution) {
            return false;
        }

        return $this->isOwnerOf($subject->accountId(), $user);
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return $this->canEdit($subject, $user);
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof OperatorContribution || $subject instanceof CompanyCarbonFootprint;
    }

    private function isOwnerOf(?AccountId $subjectAccountId, User $user): bool
    {
        $currentAccountId = $this->currentAccountId($user);

        if (!$this->userHasRole($user, Role::Owner) || !$currentAccountId instanceof AccountId) {
            return false;
        }

        return $subjectAccountId instanceof AccountId && $currentAccountId->equals($subjectAccountId);
    }
}
