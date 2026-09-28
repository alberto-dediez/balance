<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityTopic;

use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopic;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class MaterialityTopicAuthorizer extends AbstractAuthorizer
{
    protected function canRegister(mixed $subject, User $user): bool
    {
        return $this->canList($subject, $user);
    }

    protected function canView(mixed $subject, User $user): bool
    {
        return $this->canEdit($subject, $user);
    }

    protected function canList(mixed $subject, User $user): bool
    {
        return $this->userHasRole($user, Role::SuperAdmin) || $this->userHasRole($user, Role::Admin);
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        return $this->userHasRole($user, Role::SuperAdmin) || $this->userHasRole($user, Role::Admin);
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        assert($subject instanceof MaterialityTopic);

        return $this->canEdit($subject, $user) && !$subject->isStandard();
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
        return $subject instanceof MaterialityTopic;
    }
}
