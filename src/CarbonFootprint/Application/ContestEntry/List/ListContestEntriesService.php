<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ContestEntry\List;

use App\CarbonFootprint\Application\Contest\ContestAuthorizer;
use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\CarbonFootprint\Domain\Model\Contest\ContestRepository;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryFilter;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryRepository;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryStatus;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;

class ListContestEntriesService
{
    public function __construct(
        private ContestRepository $contestRepository,
        private ContestEntryRepository $contestEntryRepository,
        private ContestAuthorizer $authorizer,
    ) {}

    public function __invoke(ListContestEntriesRequest $request, User $user): ListContestEntriesResponse
    {
        $contest = $this->contestRepository->find($request->contestId);

        if (!$contest instanceof Contest) {
            throw new \InvalidArgumentException('Contest not found');
        }

        $this->authorizer->denyUnlessCanDoThat($contest, Action::View, $user);

        return new ListContestEntriesResponse(
            contest: $contest,
            entries: $this->contestEntryRepository->findByFilter($request),
            totalResults: $this->contestEntryRepository->countByFilter($request),
            disqualified: $this->contestEntryRepository->countByFilter(new ContestEntryFilter(contestId: $contest->id(), status: ContestEntryStatus::Disqualified)),
            winners: $this->contestEntryRepository->countWinners($contest->id()),
        );
    }
}
