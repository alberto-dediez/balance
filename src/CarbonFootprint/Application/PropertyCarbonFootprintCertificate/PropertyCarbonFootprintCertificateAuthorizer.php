<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificate;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class PropertyCarbonFootprintCertificateAuthorizer extends AbstractAuthorizer
{
    protected function canRegister(mixed $subject, User $user): bool
    {
        return false;
    }

    protected function canView(mixed $subject, User $user): bool
    {
        return $this->canList($subject, $user);
    }

    protected function canList(mixed $subject, User $user): bool
    {
        if ($this->userHasRole($user, Role::SuperAdmin) || $this->userHasRole($user, Role::Admin)) {
            return true;
        }

        assert($subject instanceof PropertyCarbonFootprintCertificate);

        $currentAccountId = $this->currentAccountId($user);
        $subjectAccountId = $subject->accountId();

        return $currentAccountId instanceof AccountId
            && $subjectAccountId instanceof AccountId
            && $currentAccountId->equals($subjectAccountId);
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        return false;
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return false;
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof PropertyCarbonFootprintCertificate;
    }
}
