<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ContestEntry\RemovePending;

use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryRepository;

class RemovePendingContestEntryService
{
    public function __construct(
        private ContestEntryRepository $contestEntryRepository,
    ) {}

    public function __invoke(RemovePendingContestEntryRequest $request): RemovePendingContestEntryResponse
    {
        $removed = 0;

        foreach ($this->contestEntryRepository->findByProjectContributionId($request->projectContributionId) as $entry) {
            if (!$entry->isPendingPayment()) {
                continue;
            }

            $entry->remove();
            $this->contestEntryRepository->remove($entry);
            ++$removed;
        }

        return new RemovePendingContestEntryResponse($removed);
    }
}
