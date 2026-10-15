<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\UseProfile\UseProfile;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileFilter;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileId;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryUseProfileRepository implements UseProfileRepository
{
    /** @param array<string, UseProfile> $useProfiles */
    public function __construct(
        private array $useProfiles = [],
    ) {}

    public function add(UseProfile $useProfile): void
    {
        if (isset($this->useProfiles[(string) $useProfile->id()])) {
            return;
        }

        $this->useProfiles[(string) $useProfile->id()] = $useProfile;
    }

    public function remove(UseProfile $useProfile): void
    {
        if (!isset($this->useProfiles[(string) $useProfile->id()])) {
            return;
        }

        unset($this->useProfiles[(string) $useProfile->id()]);
    }

    public function find(UseProfileId $useProfileId): ?UseProfile
    {
        return (isset($this->useProfiles[(string) $useProfileId]) && false == $this->useProfiles[(string) $useProfileId]->isRemoved())
            ? $this->useProfiles[(string) $useProfileId]
            : null
        ;
    }

    /** @return array<int, UseProfile> */
    public function findByFilter(UseProfileFilter $filter): array
    {
        $results = [];

        if (0 === count($this->useProfiles)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        $way = 'ASC' === $filter->orderWay ? 1 : -1;
        $useProfiles = array_values($this->useProfiles);
        usort(
            $useProfiles,
            static fn(UseProfile $a, UseProfile $b): int => $way * match ($filter->orderBy) {
                'registeredAt' => $a->registeredAt() <=> $b->registeredAt(),
                'updatedAt' => $a->updatedAt() <=> $b->updatedAt(),
                default => $a->name() <=> $b->name(),
            },
        );

        foreach ($useProfiles as $useProfile) {
            if (
                !empty($filter->name)
                && !str_contains(strtolower((string) $useProfile->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if (true == $useProfile->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $useProfile;
        }

        return $results;
    }

    public function totalResultsByFilter(UseProfileFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
