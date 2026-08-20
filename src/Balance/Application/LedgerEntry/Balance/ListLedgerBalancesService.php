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
use App\Shared\Application\CurrentUser\CurrentUserContext;

final class ListLedgerBalancesService
{
    public function __construct(
        private LedgerEntryRepository $repository,
        private LedgerEntryAuthorizer $authorizer,
        private CurrentUserContext $currentUserContext = new CurrentUserContext(),
    ) {}

    public function __invoke(ListLedgerBalancesRequest $request, User $user): ListLedgerBalancesResponse
    {
        if ($this->currentUserContext->hasRole($user, Role::SuperAdmin) || $this->currentUserContext->hasRole($user, Role::Admin)) {
            return new ListLedgerBalancesResponse($this->repository->balancesByBeneficiary($request->beneficiary));
        }

        $accountId = $this->currentUserContext->accountId($user);

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
