<?php

declare(strict_types=1);

namespace App\Balance\Application\LedgerEntry\Balance;

use App\Balance\Application\LedgerEntry\LedgerEntryAuthorizer;
use App\Balance\Domain\Model\LedgerEntry\LedgerBalance;
use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntry;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryId;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class ListLedgerBalancesService
{
    public function __construct(
        private LedgerEntryRepository $repository,
        private LedgerEntryAuthorizer $authorizer,
    ) {}

    public function __invoke(ListLedgerBalancesRequest $request, User $user): ListLedgerBalancesResponse
    {
        if ($user->hasRole(Role::SuperAdmin) || $user->hasRole(Role::Admin)) {
            return new ListLedgerBalancesResponse($this->repository->balancesByBeneficiary($request->beneficiary));
        }

        $accountId = $user->accountId();

        $this->authorizer->denyUnlessCanDoThat(
            new LedgerEntry(
                id: LedgerEntryId::generate(),
                beneficiary: LedgerBeneficiary::Account,
                beneficiaryId: $accountId instanceof AccountId ? (string) $accountId : null,
            ),
            SecurityAction::List,
            $user,
        );

        assert($accountId instanceof AccountId);

        /** @var array<string, LedgerBalance> $balances */
        $balances = [
            (string) $accountId => $this->repository->balanceFor(LedgerBeneficiary::Account, (string) $accountId),
        ];

        return new ListLedgerBalancesResponse($balances);
    }
}
