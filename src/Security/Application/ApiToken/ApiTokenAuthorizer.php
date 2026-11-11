<?php

declare(strict_types=1);

namespace App\Security\Application\ApiToken;

use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\ApiToken\ApiToken;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Application\CurrentAccount\CurrentAccountIdProvider;
use App\Shared\Application\CurrentRoles\CurrentRolesProvider;

final class ApiTokenAuthorizer extends AbstractAuthorizer
{
    public function __construct(
        private readonly UserRepository $userRepository,
        ?CurrentAccountIdProvider $currentAccountIdProvider = null,
        ?CurrentRolesProvider $currentRolesProvider = null,
    ) {
        parent::__construct($currentAccountIdProvider, $currentRolesProvider);
    }

    protected function canRegister(mixed $subject, User $user): bool
    {
        return $this->isOwnedBy($subject, $user);
    }

    protected function canView(mixed $subject, User $user): bool
    {
        return $this->canList($subject, $user);
    }

    protected function canList(mixed $subject, User $user): bool
    {
        return $this->isOwnedBy($subject, $user) || $this->isManagedBy($subject, $user);
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        return false;
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return $this->canList($subject, $user);
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof ApiToken;
    }

    private function isOwnedBy(mixed $subject, User $user): bool
    {
        assert($subject instanceof ApiToken);

        return $subject->belongsTo($user->id());
    }

    private function isManagedBy(mixed $subject, User $user): bool
    {
        assert($subject instanceof ApiToken);

        if ($this->userHasRole($user, Role::SuperAdmin)) {
            return true;
        }

        if ($this->userHasRole($user, Role::Admin)) {
            $tokenOwnerId = $subject->userId();
            $tokenOwner = $tokenOwnerId instanceof UserId ? $this->userRepository->find($tokenOwnerId) : null;

            return $tokenOwner instanceof User && !$tokenOwner->hasRole(Role::SuperAdmin);
        }

        $currentAccountId = $this->currentAccountId($user);
        $tokenAccountId = $subject->accountId();

        return $this->userHasRole($user, Role::Owner)
            && $currentAccountId instanceof AccountId
            && $tokenAccountId instanceof AccountId
            && $currentAccountId->equals($tokenAccountId)
        ;
    }
}
