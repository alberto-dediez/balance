<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ContestEntry;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\CarbonFootprint\Domain\Model\Contest\ContestId;

interface ContestEntryRepository
{
    public function add(ContestEntry $contestEntry): void;

    public function remove(ContestEntry $contestEntry): void;

    public function refresh(ContestEntry $contestEntry): void;

    public function find(ContestEntryId $contestEntryId): ?ContestEntry;

    /** @return list<ContestEntry> */
    public function findByFilter(ContestEntryFilter $filter): array;

    public function countByFilter(ContestEntryFilter $filter): int;

    public function findOneByOrigin(ContestId $contestId, ProjectContributionOrigin $origin, string $originId): ?ContestEntry;

    public function findOneByEmail(ContestId $contestId, string $email): ?ContestEntry;

    /** @return list<ContestEntry> */
    public function findByProjectContributionId(string $projectContributionId): array;

    /** @return list<ContestEntryId> */
    public function findEligibleWithoutPrizeIds(ContestId $contestId): array;

    public function findNextEligibleWithoutPrize(ContestId $contestId): ?ContestEntry;

    public function countWinners(ContestId $contestId): int;
}
