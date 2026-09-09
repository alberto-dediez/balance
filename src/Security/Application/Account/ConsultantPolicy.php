<?php

declare(strict_types=1);

namespace App\Security\Application\Account;

use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountFilter;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountIsNotConsultantException;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\Account\ConsultantHasAssignedAccountsException;

final class ConsultantPolicy
{
    public function __construct(private readonly AccountRepository $repository) {}

    public function denyUnlessAssignable(?AccountId $consultantAccountId): void
    {
        if (!$consultantAccountId instanceof AccountId) {
            return;
        }

        $consultant = $this->repository->find($consultantAccountId);

        if (null === $consultant || !$consultant->isConsultant()) {
            throw new AccountIsNotConsultantException($consultantAccountId);
        }
    }

    public function denyUnlessCanBeUnmarked(Account $account): void
    {
        $assigned = $this->repository->findByFilter(
            new AccountFilter(consultantAccountId: $account->id(), limit: 0),
        );

        if ([] !== $assigned) {
            throw new ConsultantHasAssignedAccountsException($account->id(), count($assigned));
        }
    }
}
