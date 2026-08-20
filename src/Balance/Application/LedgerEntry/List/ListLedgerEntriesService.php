<?php

declare(strict_types=1);

namespace App\Balance\Application\LedgerEntry\List;

use App\Balance\Application\LedgerEntry\LedgerEntryAuthorizer;
use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntry;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryId;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CurrentUser\CurrentUserContext;

class ListLedgerEntriesService
{
    public function __construct(
        private LedgerEntryRepository $repository,
        private LedgerEntryAuthorizer $authorizer,
        private CurrentUserContext $currentUserContext = new CurrentUserContext(),
    ) {}

    public function __invoke(ListLedgerEntriesRequest $request, User $user): ListLedgerEntriesResponse
    {
        $filter = $this->restrictToOwnAccount($request, $user);

        $this->authorizer->denyUnlessCanDoThat(
            new LedgerEntry(
                id: LedgerEntryId::generate(),
                beneficiary: $filter->beneficiary ?? LedgerBeneficiary::Bioscore,
                beneficiaryId: $filter->beneficiaryId,
            ),
            SecurityAction::List,
            $user,
        );

        return new ListLedgerEntriesResponse(
            $this->repository->findByFilter($filter),
            $this->repository->totalResultsByFilter($filter),
        );
    }

    private function restrictToOwnAccount(ListLedgerEntriesRequest $request, User $user): ListLedgerEntriesRequest
    {
        if ($this->currentUserContext->hasRole($user, Role::SuperAdmin) || $this->currentUserContext->hasRole($user, Role::Admin)) {
            return $request;
        }

        $accountId = $this->currentUserContext->accountId($user);

        if (!$accountId instanceof AccountId) {
            return $request;
        }

        $filter = clone $request;
        $filter->beneficiary = LedgerBeneficiary::Account;
        $filter->beneficiaryId = (string) $accountId;

        return $filter;
    }
}
