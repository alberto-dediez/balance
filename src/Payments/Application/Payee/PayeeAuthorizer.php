<?php

declare(strict_types=1);

namespace App\Payments\Application\Payee;

use App\Payments\Domain\Model\Payee\Payee;
use App\Payments\Domain\Model\Payee\PayeeType;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class PayeeAuthorizer extends AbstractAuthorizer
{
    protected function canRegister(mixed $subject, User $user): bool
    {
        if ($this->isAdministrator($user)) {
            return true;
        }

        assert($subject instanceof Payee);

        return $this->isOwnAccountPayee($subject, $user);
    }

    protected function canView(mixed $subject, User $user): bool
    {
        return $this->canRegister($subject, $user);
    }

    protected function canList(mixed $subject, User $user): bool
    {
        return $this->isAdministrator($user);
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        if ($this->isAdministrator($user)) {
            return true;
        }

        assert($subject instanceof Payee);

        return $this->isOwnAccountPayee($subject, $user) && !$subject->isBlocked();
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return $this->isAdministrator($user);
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof Payee;
    }

    private function isAdministrator(User $user): bool
    {
        return $user->hasRole(Role::SuperAdmin) || $user->hasRole(Role::Admin);
    }

    private function isOwnAccountPayee(Payee $payee, User $user): bool
    {
        $accountId = $user->accountId();

        return $user->hasRole(Role::Owner)
            && $accountId instanceof AccountId
            && PayeeType::Account === $payee->type()
            && $payee->subjectId() === (string) $accountId;
    }
}
