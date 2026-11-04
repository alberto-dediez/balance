<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\BaseYearDeclaration;

use App\CarbonFootprint\Domain\Model\BaseYearDeclaration\BaseYearDeclaration;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\Account\AccountFeature;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class BaseYearDeclarationAuthorizer extends AbstractAuthorizer
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
        assert($subject instanceof BaseYearDeclaration);

        $currentAccountId = $this->currentAccountId($user);
        $subjectAccountId = $subject->accountId();

        return $this->userHasRole($user, Role::Owner)
            && $currentAccountId instanceof AccountId
            && null !== $subjectAccountId
            && $currentAccountId->equals($subjectAccountId)
            && $this->currentAccountHasFeature($user, AccountFeature::OrganizationalCarbonFootprint);
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return $this->canEdit($subject, $user);
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof BaseYearDeclaration;
    }
}
