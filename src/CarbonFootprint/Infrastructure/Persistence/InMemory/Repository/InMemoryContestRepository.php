<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\CarbonFootprint\Domain\Model\Contest\ContestAwardMode;
use App\CarbonFootprint\Domain\Model\Contest\ContestFilter;
use App\CarbonFootprint\Domain\Model\Contest\ContestHostReference;
use App\CarbonFootprint\Domain\Model\Contest\ContestId;
use App\CarbonFootprint\Domain\Model\Contest\ContestParticipationMode;
use App\CarbonFootprint\Domain\Model\Contest\ContestRepository;
use App\CarbonFootprint\Domain\Model\Contest\ContestStatus;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryContestRepository implements ContestRepository
{
    /** @param array<string, Contest> $contests */
    public function __construct(
        private array $contests = [],
    ) {}

    public function add(Contest $contest): void
    {
        $this->contests[(string) $contest->id()] = $contest;
    }

    public function remove(Contest $contest): void
    {
        unset($this->contests[(string) $contest->id()]);
    }

    public function find(ContestId $contestId): ?Contest
    {
        return $this->contests[(string) $contestId] ?? null;
    }

    /** @return list<Contest> */
    public function findByFilter(ContestFilter $filter): array
    {
        $results = [];

        foreach ($this->contests as $contest) {
            if ($filter->accountId instanceof AccountId && !($contest->accountId()?->equals($filter->accountId) ?? false)) {
                continue;
            }

            if ($filter->status instanceof ContestStatus && $filter->status !== $contest->status()) {
                continue;
            }

            if ($filter->participationMode instanceof ContestParticipationMode && $filter->participationMode !== $contest->participationMode()) {
                continue;
            }

            if ($filter->awardMode instanceof ContestAwardMode && $filter->awardMode !== $contest->awardMode()) {
                continue;
            }

            if (
                null !== $filter->text
                && '' !== trim($filter->text)
                && !str_contains(mb_strtolower($contest->name()), mb_strtolower(trim($filter->text)))
            ) {
                continue;
            }

            $results[] = $contest;
        }

        $way = 'ASC' === $filter->orderWay ? 1 : -1;

        usort($results, static fn(Contest $a, Contest $b): int => $way * match ($filter->orderBy) {
            'startsAt' => $a->startsAt() <=> $b->startsAt(),
            'endsAt' => $a->endsAt() <=> $b->endsAt(),
            'registeredAt' => $a->registeredAt() <=> $b->registeredAt(),
            default => $a->name() <=> $b->name(),
        });

        if ($filter->limit > 0) {
            $results = array_slice($results, $filter->page * $filter->limit, $filter->limit);
        }

        return $results;
    }

    public function countByFilter(ContestFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 0;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }

    public function findPublishedByHost(ContestHostReference $host): ?Contest
    {
        foreach ($this->contests as $contest) {
            if ($contest->isPublished() && $contest->hasHost($host)) {
                return $contest;
            }
        }

        return null;
    }

    /** @return list<Contest> */
    public function findPublishedEndedBefore(\DateTimeImmutable $moment): array
    {
        return array_values(array_filter(
            $this->contests,
            static fn(Contest $contest): bool => $contest->isPublished() && $contest->endsAt() < $moment,
        ));
    }
}
