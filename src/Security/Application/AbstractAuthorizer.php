<?php

namespace App\Security\Application;

use App\Security\Domain\Model\User\User;

abstract class AbstractAuthorizer implements Authorizer
{   
    public function canDoThat(mixed $subject, Action $action, User $user): bool
    {
        if (!$this->supports($subject)) {
            return false;
        }

        return match($action) {
            Action::Register => $this->canRegister($subject, $user),
            Action::View => $this->canView($subject, $user),
            Action::List => $this->canList($subject, $user),
            Action::Edit => $this->canEdit($subject, $user),
            Action::Remove => $this->canRemove($subject, $user),
            default => throw new \LogicException('Esta acción no está definida')
        };
    }

    abstract protected function canRegister(mixed $subject, User $user): bool;
    abstract protected function canView(mixed $subject, User $user): bool;
    abstract protected function canList(mixed $subject, User $user): bool;
    abstract protected function canEdit(mixed $subject, User $user): bool;
    abstract protected function canRemove(mixed $subject, User $user): bool;
    abstract public function supports(mixed $subject): bool;
}