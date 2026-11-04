<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution;

use App\Balance\Domain\Model\ProjectContribution\DistributedContributionsResolver;
use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Application\Account\AccountFeaturesPolicy;
use App\Security\Application\Account\ConsultantSupervisionPolicy;
use App\Security\Application\Account\DistributorPolicy;
use App\Security\Domain\Model\Account\AccountFeature;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CurrentAccount\CurrentAccountIdProvider;
use App\Shared\Application\CurrentImpersonation\CurrentImpersonationProvider;
use App\Shared\Application\CurrentRoles\CurrentRolesProvider;

final class ProjectContributionAuthorizer extends AbstractAuthorizer
{
    public function __construct(
        ?CurrentAccountIdProvider $currentAccountIdProvider = null,
        ?CurrentRolesProvider $currentRolesProvider = null,
        ?CurrentImpersonationProvider $currentImpersonationProvider = null,
        ?ConsultantSupervisionPolicy $consultantSupervisionPolicy = null,
        ?AccountFeaturesPolicy $accountFeaturesPolicy = null,
        private readonly ?DistributorPolicy $distributorPolicy = null,
        private readonly ?DistributedContributionsResolver $distributedContributionsResolver = null,
    ) {
        parent::__construct($currentAccountIdProvider, $currentRolesProvider, $currentImpersonationProvider, $consultantSupervisionPolicy, $accountFeaturesPolicy);
    }

    public function isActiveDistributorOwner(User $user, AccountId $accountId): bool
    {
        $currentAccountId = $this->currentAccountId($user);

        return $this->distributorPolicy instanceof DistributorPolicy
            && $this->userHasRole($user, Role::Owner)
            && $currentAccountId instanceof AccountId
            && $currentAccountId->equals($accountId)
            && $this->distributorPolicy->isActiveDistributor($accountId);
    }

    protected function canRegister(mixed $subject, User $user): bool
    {
        assert($subject instanceof ProjectContribution);

        $currentAccountId = $this->currentAccountId($user);
        $subjectAccountId = $subject->accountId();

        return $this->userHasRole($user, Role::Owner)
            && $currentAccountId instanceof AccountId
            && null !== $subjectAccountId
            && $currentAccountId->equals($subjectAccountId)
            && $this->currentAccountHasFeature($user, AccountFeature::Compensation);
    }

    protected function canView(mixed $subject, User $user): bool
    {
        assert($subject instanceof ProjectContribution);

        return $this->canList($subject, $user) || $this->isDistributedToCurrentAccount($subject, $user);
    }

    protected function canList(mixed $subject, User $user): bool
    {
        if ($this->userHasRole($user, Role::SuperAdmin) || $this->userHasRole($user, Role::Admin)) {
            return true;
        }

        assert($subject instanceof ProjectContribution);

        $currentAccountId = $this->currentAccountId($user);
        $subjectAccountId = $subject->accountId();

        if (!$currentAccountId instanceof AccountId || null === $subjectAccountId || !$currentAccountId->equals($subjectAccountId)) {
            return false;
        }

        return $this->currentAccountHasFeature($user, AccountFeature::Compensation)
            || $this->isActiveDistributorOwner($user, $subjectAccountId);
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        return $this->userHasRole($user, Role::SuperAdmin) || $this->userHasRole($user, Role::Admin);
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return false;
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof ProjectContribution;
    }

    private function isDistributedToCurrentAccount(ProjectContribution $projectContribution, User $user): bool
    {
        $currentAccountId = $this->currentAccountId($user);

        return $this->distributedContributionsResolver instanceof DistributedContributionsResolver
            && $currentAccountId instanceof AccountId
            && $this->isActiveDistributorOwner($user, $currentAccountId)
            && $this->distributedContributionsResolver->distributes($currentAccountId, $projectContribution);
    }
}
