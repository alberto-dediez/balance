<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\Persistence\InMemory\Repository;

use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopic;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicFilter;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryMaterialityTopicRepository implements MaterialityTopicRepository
{
    /** @param array<string, MaterialityTopic> $materialityTopics */
    public function __construct(
        private array $materialityTopics = [],
    ) {}

    public function add(MaterialityTopic $materialityTopic): void
    {
        $this->materialityTopics[(string) $materialityTopic->id()] = $materialityTopic;
    }

    public function remove(MaterialityTopic $materialityTopic): void
    {
        unset($this->materialityTopics[(string) $materialityTopic->id()]);
    }

    public function find(MaterialityTopicId $materialityTopicId): ?MaterialityTopic
    {
        $materialityTopic = $this->materialityTopics[(string) $materialityTopicId] ?? null;

        if (null === $materialityTopic || false != $materialityTopic->isRemoved()) {
            return null;
        }

        return $materialityTopic;
    }

    public function findByNumber(int $number): ?MaterialityTopic
    {
        foreach ($this->materialityTopics as $materialityTopic) {
            if (false == $materialityTopic->isRemoved() && $materialityTopic->number() === $number) {
                return $materialityTopic;
            }
        }

        return null;
    }

    /** @return array<int, MaterialityTopic> */
    public function findByFilter(MaterialityTopicFilter $filter): array
    {
        $matching = [];

        foreach ($this->materialityTopics as $materialityTopic) {
            if (!$this->matches($materialityTopic, $filter)) {
                continue;
            }

            $matching[] = $materialityTopic;
        }

        $this->sort($matching, $filter);

        if ($filter->limit > 0) {
            return array_slice($matching, $filter->page * $filter->limit, $filter->limit);
        }

        return $matching;
    }

    public function totalResultsByFilter(MaterialityTopicFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 0;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }

    /** @return list<MaterialityTopic> */
    public function findActive(): array
    {
        return array_values($this->findByFilter(new MaterialityTopicFilter(active: true, limit: 0)));
    }

    private function matches(MaterialityTopic $materialityTopic, MaterialityTopicFilter $filter): bool
    {
        if (!$filter->includeRemoved && false != $materialityTopic->isRemoved()) {
            return false;
        }

        if (null !== $filter->name && '' !== $filter->name && !str_contains(mb_strtolower($materialityTopic->name()), mb_strtolower($filter->name))) {
            return false;
        }

        if (null !== $filter->scope && $materialityTopic->scope() !== $filter->scope) {
            return false;
        }

        if (null !== $filter->direction && $materialityTopic->direction() !== $filter->direction) {
            return false;
        }

        if (null !== $filter->active && $materialityTopic->isActive() !== $filter->active) {
            return false;
        }

        if (null !== $filter->standard && $materialityTopic->isStandard() !== $filter->standard) {
            return false;
        }

        return true;
    }

    /** @param list<MaterialityTopic> $materialityTopics */
    private function sort(array &$materialityTopics, MaterialityTopicFilter $filter): void
    {
        $way = 'ASC' === $filter->orderWay ? 1 : -1;

        usort($materialityTopics, static function (MaterialityTopic $a, MaterialityTopic $b) use ($filter, $way): int {
            $comparison = match ($filter->orderBy) {
                'name' => strcmp($a->name(), $b->name()),
                'number' => $a->number() <=> $b->number(),
                'registeredAt' => $a->registeredAt() <=> $b->registeredAt(),
                'updatedAt' => $a->updatedAt() <=> $b->updatedAt(),
                default => 0 !== ($a->position() <=> $b->position()) ? $a->position() <=> $b->position() : strcmp($a->name(), $b->name()),
            };

            return 0 !== $comparison ? $comparison * $way : strcmp((string) $a->id(), (string) $b->id());
        });
    }
}
