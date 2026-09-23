<?php

declare(strict_types=1);

namespace App\Changelog\Application\Announcement;

use App\Changelog\Domain\Model\Announcement\Announcement;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CurrentAccount\CurrentAccountIdProvider;
use App\Shared\Application\CurrentRoles\CurrentRolesProvider;

final class AnnouncementAuthorizer extends AbstractAuthorizer
{
    public function __construct(
        ?CurrentAccountIdProvider $currentAccountIdProvider = null,
        ?CurrentRolesProvider $currentRolesProvider = null,
        private readonly bool $announcementsEnabled = true,
    ) {
        parent::__construct($currentAccountIdProvider, $currentRolesProvider);
    }

    protected function canRegister(mixed $subject, User $user): bool
    {
        return $this->canAdmin($subject, $user);
    }

    protected function canView(mixed $subject, User $user): bool
    {
        if ($subject->isPublished() && $this->announcementsEnabled) {
            return true;
        }

        return $this->canAdmin($subject, $user);
    }

    protected function canList(mixed $subject, User $user): bool
    {
        if ($this->announcementsEnabled) {
            return true;
        }

        return $this->canAdmin($subject, $user);
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        return $this->canAdmin($subject, $user);
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return $this->canAdmin($subject, $user);
    }

    protected function canHistory(mixed $subject, User $user): bool
    {
        return $this->canAdmin($subject, $user);
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof Announcement;
    }
}
