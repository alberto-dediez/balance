<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\CarbonFootprint\Domain\Model\Contest\ContestId;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntry;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryAlreadyExistsException;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryFilter;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryId;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryRepository;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryStatus;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryContestEntryRepository implements ContestEntryRepository
{
    /** @param array<string, ContestEntry> $entries */
    public function __construct(
        private array $entries = [],
    ) {}

    public function add(ContestEntry $contestEntry): void
    {
        foreach ($this->entries as $id => $entry) {
            if ($id === (string) $contestEntry->id() || !$this->sameContest($entry, $contestEntry)) {
                continue;
            }

            if (
                ($entry->origin() === $contestEntry->origin() && $entry->originId() === $contestEntry->originId())
                || (null !== $entry->email() && $entry->email() === $contestEntry->email())
                || (null !== $entry->entryNumber() && $entry->entryNumber() === $contestEntry->entryNumber())
            ) {
                throw new ContestEntryAlreadyExistsException();
            }
        }

        $this->entries[(string) $contestEntry->id()] = $contestEntry;
    }

    public function remove(ContestEntry $contestEntry): void
    {
        unset($this->entries[(string) $contestEntry->id()]);
    }

    public function refresh(ContestEntry $contestEntry): void {}

    public function find(ContestEntryId $contestEntryId): ?ContestEntry
    {
        return $this->entries[(string) $contestEntryId] ?? null;
    }

    /** @return list<ContestEntry> */
    public function findByFilter(ContestEntryFilter $filter): array
    {
        $results = [];

        foreach ($this->entries as $entry) {
            if (!$this->belongsTo($entry, $filter->contestId)) {
                continue;
            }

            if ($filter->status instanceof ContestEntryStatus && $filter->status !== $entry->status()) {
                continue;
            }

            if (null !== $filter->winner && $filter->winner !== $entry->isWinner()) {
                continue;
            }

            if (null !== $filter->text && '' !== trim($filter->text)) {
                $text = mb_strtolower(trim($filter->text));

                if (!str_contains(mb_strtolower($entry->name()), $text) && !str_contains(mb_strtolower((string) $entry->email()), $text)) {
                    continue;
                }
            }

            $results[] = $entry;
        }

        $way = 'ASC' === $filter->orderWay ? 1 : -1;

        usort($results, static fn(ContestEntry $a, ContestEntry $b): int => $way * match ($filter->orderBy) {
            'name' => $a->name() <=> $b->name(),
            'email' => $a->email() <=> $b->email(),
            'confirmedAt' => $a->confirmedAt() <=> $b->confirmedAt(),
            default => [$a->entryNumber(), $a->registeredAt()] <=> [$b->entryNumber(), $b->registeredAt()],
        });

        if ($filter->limit > 0) {
            $results = array_slice($results, $filter->page * $filter->limit, $filter->limit);
        }

        return $results;
    }

    public function countByFilter(ContestEntryFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 0;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }

    public function findOneByOrigin(ContestId $contestId, ProjectContributionOrigin $origin, string $originId): ?ContestEntry
    {
        foreach ($this->entries as $entry) {
            if ($this->belongsTo($entry, $contestId) && $entry->origin() === $origin && $entry->originId() === $originId) {
                return $entry;
            }
        }

        return null;
    }

    public function findOneByEmail(ContestId $contestId, string $email): ?ContestEntry
    {
        $email = mb_strtolower(trim($email));

        foreach ($this->entries as $entry) {
            if ($this->belongsTo($entry, $contestId) && $entry->email() === $email) {
                return $entry;
            }
        }

        return null;
    }

    /** @return list<ContestEntry> */
    public function findByProjectContributionId(string $projectContributionId): array
    {
        return array_values(array_filter(
            $this->entries,
            static fn(ContestEntry $entry): bool => $entry->projectContributionId() === $projectContributionId,
        ));
    }

    /** @return list<ContestEntryId> */
    public function findEligibleWithoutPrizeIds(ContestId $contestId): array
    {
        return array_map(static fn(ContestEntry $entry): ContestEntryId => $entry->id(), $this->eligibleWithoutPrize($contestId));
    }

    public function findNextEligibleWithoutPrize(ContestId $contestId): ?ContestEntry
    {
        return $this->eligibleWithoutPrize($contestId)[0] ?? null;
    }

    public function countWinners(ContestId $contestId): int
    {
        return count(array_filter(
            $this->entries,
            fn(ContestEntry $entry): bool => $this->belongsTo($entry, $contestId) && $entry->isWinner(),
        ));
    }

    /** @return list<ContestEntry> */
    private function eligibleWithoutPrize(ContestId $contestId): array
    {
        $entries = array_values(array_filter(
            $this->entries,
            fn(ContestEntry $entry): bool => $this->belongsTo($entry, $contestId) && $entry->isEligible() && !$entry->isWinner(),
        ));

        usort($entries, static fn(ContestEntry $a, ContestEntry $b): int => $a->entryNumber() <=> $b->entryNumber());

        return $entries;
    }

    private function belongsTo(ContestEntry $entry, ContestId $contestId): bool
    {
        return $entry->contestId()?->equals($contestId) ?? false;
    }

    private function sameContest(ContestEntry $entry, ContestEntry $other): bool
    {
        $contestId = $other->contestId();

        return null !== $contestId && $this->belongsTo($entry, $contestId);
    }
}
