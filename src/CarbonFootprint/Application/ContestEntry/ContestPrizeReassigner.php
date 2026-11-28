<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ContestEntry;

use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\CarbonFootprint\Domain\Model\Contest\ContestRepository;
use App\CarbonFootprint\Domain\Model\Contest\ContestWinnerPicker;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryRepository;

final class ContestPrizeReassigner
{
    public function __construct(
        private readonly ContestRepository $contestRepository,
        private readonly ContestEntryRepository $contestEntryRepository,
        private readonly ContestWinnerPicker $winnerPicker,
    ) {}

    public function reassign(Contest $contest, \DateTimeImmutable $now): void
    {
        if (!$contest->reassignsPrizes()) {
            return;
        }

        if ($contest->awardMode()->isFirstEntrants()) {
            $next = $this->contestEntryRepository->findNextEligibleWithoutPrize($contest->id());

            if (null !== $next) {
                $next->award($now);
                $this->contestEntryRepository->add($next);
            }

            return;
        }

        $picked = $this->winnerPicker->pick($this->contestEntryRepository->findEligibleWithoutPrizeIds($contest->id()), 1);
        $entry = isset($picked[0]) ? $this->contestEntryRepository->find($picked[0]) : null;

        if (null === $entry) {
            return;
        }

        $entry->award($now);
        $this->contestEntryRepository->add($entry);

        $contest->recordPrizeRedrawn($now);
        $this->contestRepository->add($contest);
    }
}
