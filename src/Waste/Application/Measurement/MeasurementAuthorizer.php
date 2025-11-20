<?php

namespace App\Waste\Application\Measurement;

use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Application\AbstractAuthorizer;
use App\Waste\Domain\Model\Measurement\Measurement;

final class MeasurementAuthorizer extends AbstractAuthorizer
{
    protected function canRegister(mixed $subject, User $user): bool
    {
        if (null == $user->accountId() || null == $subject->accountId()) {
            return false;
        }

        return $user->accountId()->equals($subject->accountId());
    }

    protected function canView(mixed $subject, User $user): bool
    {
        if (null == $user->accountId()) {
            return false;
        }

        return
            $user->accountId()->equals($subject->accountId())
            && (
                $user->hasRole(Role::Owner)
                || $user->id()->equals($subject->registeredBy())
            )
        ;
    }

    protected function canList(mixed $subject, User $user): bool
    {
        if (null == $user->accountId() || null == $subject->accountId() || false == $user->accountId()->equals($subject->accountId())) {
            return false;
        }

        return true;
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        if (null == $user->accountId() || null == $subject->accountId()) {
            return false;
        }

        return
            $user->accountId()->equals($subject->accountId())
            && (
                $user->hasRole(Role::Owner)
                || $user->id()->equals($subject->registeredBy())
            )
        ;
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        if (null == $user->accountId()) {
            return false;
        }

        return
            $user->accountId()->equals($subject->accountId())
            && (
                $user->hasRole(Role::Owner)
            )
        ;
    }

    protected function canImport(mixed $subject, User $user): bool
    {
        if (null == $user->accountId() || null == $subject->accountId()) {
            return false;
        }

        return
            $user->accountId()->equals($subject->accountId())
            && (
                $user->hasRole(Role::Owner)
            )
        ;
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof Measurement;
    }
}
