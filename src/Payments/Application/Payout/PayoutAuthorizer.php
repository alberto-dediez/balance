<?php

declare(strict_types=1);

namespace App\Payments\Application\Payout;

use App\Payments\Domain\Model\Payout\Payout;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class PayoutAuthorizer extends AbstractAuthorizer
{
    protected function canRegister(mixed $subject, User $user): bool
    {
        return $this->userHasRole($user, Role::SuperAdmin) || $this->userHasRole($user, Role::Admin);
    }

    protected function canView(mixed $subject, User $user): bool
    {
        return $this->canList($subject, $user);
    }

    protected function canList(mixed $subject, User $user): bool
    {
        return $this->canRegister($subject, $user) || $this->userHasRole($user, Role::Owner);
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        return $this->canRegister($subject, $user);
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return $this->canRegister($subject, $user);
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof Payout;
    }
}
