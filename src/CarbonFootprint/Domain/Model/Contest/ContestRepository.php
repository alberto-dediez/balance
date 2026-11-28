<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Contest;

interface ContestRepository
{
    public function add(Contest $contest): void;

    public function remove(Contest $contest): void;

    public function find(ContestId $contestId): ?Contest;

    /** @return list<Contest> */
    public function findByFilter(ContestFilter $filter): array;

    public function countByFilter(ContestFilter $filter): int;

    public function findPublishedByHost(ContestHostReference $host): ?Contest;

    /** @return list<Contest> */
    public function findPublishedEndedBefore(\DateTimeImmutable $moment): array;
}
