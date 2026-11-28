<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ContestEntry\Confirm;

use App\CarbonFootprint\Application\ContestEntry\ContestEntryConfirmer;
use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\CarbonFootprint\Domain\Model\Contest\ContestLock;
use App\CarbonFootprint\Domain\Model\Contest\ContestRepository;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryRepository;
use App\Shared\Application\Transaction\TransactionalSession;

class ConfirmContestEntryService
{
    public function __construct(
        private ContestRepository $contestRepository,
        private ContestEntryRepository $contestEntryRepository,
        private ContestEntryConfirmer $confirmer,
        private ContestLock $contestLock,
        private TransactionalSession $transactionalSession,
    ) {}

    public function __invoke(ConfirmContestEntryRequest $request): ConfirmContestEntryResponse
    {
        $confirmed = [];

        foreach ($this->contestEntryRepository->findByProjectContributionId($request->projectContributionId) as $entry) {
            $contestId = $entry->contestId();

            if (!$entry->isPendingPayment() || null === $contestId) {
                continue;
            }

            $this->transactionalSession->run(function () use ($entry, $contestId, $request): void {
                $this->contestLock->acquire($contestId);
                $this->contestEntryRepository->refresh($entry);

                if (!$entry->isPendingPayment()) {
                    return;
                }

                $contest = $this->contestRepository->find($contestId);

                if (!$contest instanceof Contest) {
                    return;
                }

                $this->confirmer->confirm($contest, $entry, $request->now);
            });

            $confirmed[] = $entry;
        }

        return new ConfirmContestEntryResponse($confirmed);
    }
}
