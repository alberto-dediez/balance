<?php

declare(strict_types=1);

namespace App\Changelog\Application\GuideArticle;

use App\Changelog\Domain\Model\GuideArticle\GuideArticle;
use App\Changelog\Domain\Model\GuideArticle\GuideProfile;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class GuideArticleAuthorizer extends AbstractAuthorizer
{
    public function profileOf(User $user): GuideProfile
    {
        if ($this->userHasRole($user, Role::SuperAdmin) || $this->userHasRole($user, Role::Admin)) {
            return GuideProfile::Admin;
        }

        if ($this->userHasRole($user, Role::Owner)) {
            return GuideProfile::Owner;
        }

        return GuideProfile::User;
    }

    protected function canList(mixed $subject, User $user): bool
    {
        return true;
    }

    protected function canView(mixed $subject, User $user): bool
    {
        return $subject instanceof GuideArticle && $subject->profile === $this->profileOf($user);
    }

    protected function canRegister(mixed $subject, User $user): bool
    {
        return false;
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        return false;
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return false;
    }

    protected function canHistory(mixed $subject, User $user): bool
    {
        return false;
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof GuideArticle;
    }
}
