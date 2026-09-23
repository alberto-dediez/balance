<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReport;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportFilter;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportId;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryCarbonFootprintReportRepository implements CarbonFootprintReportRepository
{
    /** @param array<string, CarbonFootprintReport> $reports */
    public function __construct(
        private array $reports = [],
    ) {}

    public function add(CarbonFootprintReport $report): void
    {
        $this->reports[(string) $report->id()] = $report;
    }

    public function remove(CarbonFootprintReport $report): void
    {
        unset($this->reports[(string) $report->id()]);
    }

    public function find(CarbonFootprintReportId $id): ?CarbonFootprintReport
    {
        return $this->reports[(string) $id] ?? null;
    }

    public function findBySubject(CarbonFootprintReportSubjectType $subjectType, string $carbonFootprintId): ?CarbonFootprintReport
    {
        foreach ($this->reports as $report) {
            if ($subjectType === $report->subjectType() && $carbonFootprintId === $report->carbonFootprintId()) {
                return $report;
            }
        }

        return null;
    }

    /** @return list<CarbonFootprintReport> */
    public function findByFilter(CarbonFootprintReportFilter $filter): array
    {
        $results = $this->matching($filter);

        $way = 'ASC' === $filter->orderWay ? 1 : -1;

        usort($results, static function (CarbonFootprintReport $a, CarbonFootprintReport $b) use ($filter, $way): int {
            $valueA = 'submittedAt' === $filter->orderBy ? $a->submittedAt() : $a->registeredAt();
            $valueB = 'submittedAt' === $filter->orderBy ? $b->submittedAt() : $b->registeredAt();

            return $way * ($valueA <=> $valueB);
        });

        if ($filter->limit > 0) {
            $results = array_slice($results, $filter->page * $filter->limit, $filter->limit);
        }

        return $results;
    }

    public function totalResultsByFilter(CarbonFootprintReportFilter $filter): int
    {
        return count($this->matching($filter));
    }

    /** @return list<CarbonFootprintReport> */
    private function matching(CarbonFootprintReportFilter $filter): array
    {
        $results = [];

        foreach ($this->reports as $report) {
            if (null !== $filter->status && $filter->status !== $report->status()) {
                continue;
            }

            if (null !== $filter->subjectType && $filter->subjectType !== $report->subjectType()) {
                continue;
            }

            $accountId = $report->accountId();

            if ($filter->accountId instanceof AccountId && (!$accountId instanceof AccountId || !$filter->accountId->equals($accountId))) {
                continue;
            }

            $results[] = $report;
        }

        return $results;
    }
}
