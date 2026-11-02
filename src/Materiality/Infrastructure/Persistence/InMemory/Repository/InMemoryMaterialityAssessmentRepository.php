<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\Persistence\InMemory\Repository;

use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessment;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentFilter;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentId;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentRepository;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentSubjectType;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryMaterialityAssessmentRepository implements MaterialityAssessmentRepository
{
    /** @param array<string, MaterialityAssessment> $materialityAssessments */
    public function __construct(
        private array $materialityAssessments = [],
    ) {}

    public function add(MaterialityAssessment $materialityAssessment): void
    {
        $this->materialityAssessments[(string) $materialityAssessment->id()] = $materialityAssessment;
    }

    public function remove(MaterialityAssessment $materialityAssessment): void
    {
        unset($this->materialityAssessments[(string) $materialityAssessment->id()]);
    }

    public function find(MaterialityAssessmentId $materialityAssessmentId): ?MaterialityAssessment
    {
        $materialityAssessment = $this->materialityAssessments[(string) $materialityAssessmentId] ?? null;

        if (null === $materialityAssessment || $materialityAssessment->isRemoved()) {
            return null;
        }

        return $materialityAssessment;
    }

    public function findCurrentByCarbonFootprint(MaterialityAssessmentSubjectType $subjectType, string $carbonFootprintId): ?MaterialityAssessment
    {
        $filter = new MaterialityAssessmentFilter(subjectType: $subjectType, carbonFootprintId: $carbonFootprintId, limit: 1);

        return $this->findByFilter($filter)[0] ?? null;
    }

    /** @return list<MaterialityAssessment> */
    public function findByFilter(MaterialityAssessmentFilter $filter): array
    {
        $matching = [];

        foreach ($this->materialityAssessments as $materialityAssessment) {
            if (!$this->matches($materialityAssessment, $filter)) {
                continue;
            }

            $matching[] = $materialityAssessment;
        }

        $this->sort($matching, $filter);

        if ($filter->limit > 0) {
            return array_slice($matching, $filter->page * $filter->limit, $filter->limit);
        }

        return $matching;
    }

    public function totalResultsByFilter(MaterialityAssessmentFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 0;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }

    private function matches(MaterialityAssessment $materialityAssessment, MaterialityAssessmentFilter $filter): bool
    {
        if (!$filter->includeRemoved && $materialityAssessment->isRemoved()) {
            return false;
        }

        if (null !== $filter->subjectType && $materialityAssessment->subjectType() !== $filter->subjectType) {
            return false;
        }

        if (null !== $filter->carbonFootprintId && $materialityAssessment->carbonFootprintId() !== $filter->carbonFootprintId) {
            return false;
        }

        if (null !== $filter->accountId && (string) $materialityAssessment->accountId() !== (string) $filter->accountId) {
            return false;
        }

        if (null !== $filter->year && $materialityAssessment->year() !== $filter->year) {
            return false;
        }

        if (null !== $filter->status && $materialityAssessment->status() !== $filter->status) {
            return false;
        }

        return true;
    }

    /** @param list<MaterialityAssessment> $materialityAssessments */
    private function sort(array &$materialityAssessments, MaterialityAssessmentFilter $filter): void
    {
        $way = 'ASC' === $filter->orderWay ? 1 : -1;

        usort($materialityAssessments, static function (MaterialityAssessment $a, MaterialityAssessment $b) use ($filter, $way): int {
            $comparison = match ($filter->orderBy) {
                'year' => ($a->year() <=> $b->year()) * $way,
                'registeredAt' => ($a->registeredAt() <=> $b->registeredAt()) * $way,
                'completedAt' => ($a->completedAt() <=> $b->completedAt()) * $way,
                default => $b->registeredAt() <=> $a->registeredAt(),
            };

            return 0 !== $comparison ? $comparison : strcmp((string) $a->id(), (string) $b->id());
        });
    }
}
