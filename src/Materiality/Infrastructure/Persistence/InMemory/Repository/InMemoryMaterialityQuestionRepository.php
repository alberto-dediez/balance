<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\Persistence\InMemory\Repository;

use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestion;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionFilter;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionId;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionRepository;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryMaterialityQuestionRepository implements MaterialityQuestionRepository
{
    /** @param array<string, MaterialityQuestion> $materialityQuestions */
    public function __construct(
        private array $materialityQuestions = [],
    ) {}

    public function add(MaterialityQuestion $materialityQuestion): void
    {
        $this->materialityQuestions[(string) $materialityQuestion->id()] = $materialityQuestion;
    }

    public function remove(MaterialityQuestion $materialityQuestion): void
    {
        unset($this->materialityQuestions[(string) $materialityQuestion->id()]);
    }

    public function find(MaterialityQuestionId $materialityQuestionId): ?MaterialityQuestion
    {
        $materialityQuestion = $this->materialityQuestions[(string) $materialityQuestionId] ?? null;

        if (null === $materialityQuestion || false != $materialityQuestion->isRemoved()) {
            return null;
        }

        return $materialityQuestion;
    }

    /** @return array<int, MaterialityQuestion> */
    public function findByFilter(MaterialityQuestionFilter $filter): array
    {
        $matching = [];

        foreach ($this->materialityQuestions as $materialityQuestion) {
            if (!$this->matches($materialityQuestion, $filter)) {
                continue;
            }

            $matching[] = $materialityQuestion;
        }

        $this->sort($matching, $filter);

        if ($filter->limit > 0) {
            return array_slice($matching, $filter->page * $filter->limit, $filter->limit);
        }

        return $matching;
    }

    public function totalResultsByFilter(MaterialityQuestionFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 0;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }

    /** @return list<MaterialityQuestion> */
    public function findByTopicId(MaterialityTopicId $topicId): array
    {
        return array_values($this->findByFilter(new MaterialityQuestionFilter(topicId: $topicId, limit: 0)));
    }

    private function matches(MaterialityQuestion $materialityQuestion, MaterialityQuestionFilter $filter): bool
    {
        if (!$filter->includeRemoved && false != $materialityQuestion->isRemoved()) {
            return false;
        }

        if (null !== $filter->text && '' !== $filter->text && !str_contains(mb_strtolower($materialityQuestion->text()), mb_strtolower($filter->text))) {
            return false;
        }

        if (null !== $filter->criterion && $materialityQuestion->criterion() !== $filter->criterion) {
            return false;
        }

        if (null !== $filter->topicId && (string) $materialityQuestion->topicId() !== (string) $filter->topicId) {
            return false;
        }

        if ($filter->onlyGeneric && !$materialityQuestion->isGeneric()) {
            return false;
        }

        if (null !== $filter->active && $materialityQuestion->isActive() !== $filter->active) {
            return false;
        }

        return true;
    }

    /** @param list<MaterialityQuestion> $materialityQuestions */
    private function sort(array &$materialityQuestions, MaterialityQuestionFilter $filter): void
    {
        $way = 'ASC' === $filter->orderWay ? 1 : -1;

        usort($materialityQuestions, static function (MaterialityQuestion $a, MaterialityQuestion $b) use ($filter, $way): int {
            $comparison = match ($filter->orderBy) {
                'criterion' => strcmp($a->criterion()->value, $b->criterion()->value),
                'registeredAt' => $a->registeredAt() <=> $b->registeredAt(),
                default => 0 !== ($a->position() <=> $b->position()) ? $a->position() <=> $b->position() : $a->registeredAt() <=> $b->registeredAt(),
            };

            return 0 !== $comparison ? $comparison * $way : strcmp((string) $a->id(), (string) $b->id());
        });
    }
}
