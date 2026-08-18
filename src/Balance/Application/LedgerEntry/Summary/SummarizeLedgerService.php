<?php

declare(strict_types=1);

namespace App\Balance\Application\LedgerEntry\Summary;

use App\Balance\Application\LedgerEntry\LedgerEntryAuthorizer;
use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntry;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryFilter;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryId;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

final class SummarizeLedgerService
{
    public function __construct(
        private LedgerEntryRepository $repository,
        private LedgerEntryAuthorizer $authorizer,
    ) {}

    public function __invoke(SummarizeLedgerRequest $request, User $user): SummarizeLedgerResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new LedgerEntry(id: LedgerEntryId::generate()), SecurityAction::Admin, $user);

        $totalsByBeneficiary = [];
        $pendingByBeneficiary = [];

        foreach ($this->beneficiaryFilters($request) as $beneficiary => $filter) {
            $totalsByBeneficiary[$beneficiary] = $this->sum($filter);

            $pendingFilter = clone $filter;
            $pendingFilter->settled = false;

            $pendingByBeneficiary[$beneficiary] = $this->sum($pendingFilter);
        }

        return new SummarizeLedgerResponse(
            totalsByType: $this->repository->totalsByType($request),
            totalsByBeneficiary: $totalsByBeneficiary,
            pendingByBeneficiary: $pendingByBeneficiary,
        );
    }

    /** @return array<string, LedgerEntryFilter> */
    private function beneficiaryFilters(SummarizeLedgerRequest $request): array
    {
        $filters = [];

        foreach (LedgerBeneficiary::cases() as $beneficiary) {
            $filter = clone $request;
            $filter->beneficiary = $beneficiary;

            $filters[$beneficiary->value] = $filter;
        }

        return $filters;
    }

    private function sum(LedgerEntryFilter $filter): float
    {
        return round(array_sum($this->repository->totalsByType($filter)), 2);
    }
}
