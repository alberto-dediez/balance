<?php

declare(strict_types=1);

namespace App\Balance\Application\Settlement\Unsettle;

use App\Balance\Domain\Model\LedgerEntry\LedgerEntryRepository;

final class UnsettleLedgerEntriesService
{
    public function __construct(private LedgerEntryRepository $ledgerEntryRepository) {}

    public function __invoke(UnsettleLedgerEntriesRequest $request): UnsettleLedgerEntriesResponse
    {
        $released = 0;

        foreach ($this->ledgerEntryRepository->findByPayout($request->payoutId) as $entry) {
            $entry->unsettle();

            $this->ledgerEntryRepository->add($entry);

            ++$released;
        }

        return new UnsettleLedgerEntriesResponse($released);
    }
}
