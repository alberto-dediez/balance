<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ContestEntry;

use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\CarbonFootprint\Domain\Model\Contest\ContestRepository;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntry;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryRepository;

final class ContestEntryConfirmer
{
    public function __construct(
        private readonly ContestRepository $contestRepository,
        private readonly ContestEntryRepository $contestEntryRepository,
    ) {}

    public function confirm(Contest $contest, ContestEntry $entry, \DateTimeImmutable $now): void
    {
        if (!$entry->isPendingPayment()) {
            return;
        }

        $entry->confirm($contest->countConfirmedEntry(), $now);

        if ($contest->awardsOnConfirmation($this->contestEntryRepository->countWinners($contest->id()))) {
            $entry->award($now);
        }

        $this->contestRepository->add($contest);
        $this->contestEntryRepository->add($entry);
    }
}
