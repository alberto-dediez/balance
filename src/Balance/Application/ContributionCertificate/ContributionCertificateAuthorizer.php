<?php

declare(strict_types=1);

namespace App\Balance\Application\ContributionCertificate;

use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificate;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

/**
 * Certificates are issued by the payment flow and never edited, so the backoffice only reads them:
 * an account may look at its own, administrators at every one.
 */
final class ContributionCertificateAuthorizer extends AbstractAuthorizer
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
        if ($user->hasRole(Role::SuperAdmin) || $user->hasRole(Role::Admin)) {
            return true;
        }

        assert($subject instanceof ContributionCertificate);

        $subjectAccountId = $subject->accountId();

        return $user->accountId() instanceof AccountId
            && $subjectAccountId instanceof AccountId
            && $user->accountId()->equals($subjectAccountId);
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
        return $subject instanceof ContributionCertificate;
    }
}
