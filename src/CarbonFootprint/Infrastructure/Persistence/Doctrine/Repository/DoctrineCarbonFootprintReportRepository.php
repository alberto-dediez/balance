<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReport;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportFilter;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportId;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.carbon_footprint_report', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineCarbonFootprintReportRepository implements CarbonFootprintReportRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(CarbonFootprintReport $report): void
    {
        $this->manager->persist($report);
        $this->manager->flush();
    }

    public function remove(CarbonFootprintReport $report): void
    {
        $this->manager->remove($report);
        $this->manager->flush();
    }

    public function find(CarbonFootprintReportId $id): ?CarbonFootprintReport
    {
        return $this->manager->find(CarbonFootprintReport::class, $id);
    }

    public function findBySubject(CarbonFootprintReportSubjectType $subjectType, string $carbonFootprintId): ?CarbonFootprintReport
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb
            ->select('r')
            ->from(CarbonFootprintReport::class, 'r')
            ->where($qb->expr()->eq('r.subjectType', ':subjectType'))
            ->andWhere($qb->expr()->eq('r.carbonFootprintId', ':carbonFootprintId'))
            ->setParameter('subjectType', $subjectType->value)
            ->setParameter('carbonFootprintId', $carbonFootprintId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /** @return array<CarbonFootprintReport> */
    public function findByFilter(CarbonFootprintReportFilter $filter): array
    {
        $query = $this->createQueryBuilderFromFilter($filter)->getQuery();

        if ($filter->limit > 0) {
            $query
                ->setMaxResults($filter->limit)
                ->setFirstResult($filter->page * $filter->limit)
            ;
        }

        return (array) $query->getResult();
    }

    public function totalResultsByFilter(CarbonFootprintReportFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT r)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function createQueryBuilderFromFilter(CarbonFootprintReportFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('r')
            ->from(CarbonFootprintReport::class, 'r')
        ;

        if (null !== $filter->status) {
            $qb
                ->andWhere($qb->expr()->eq('r.status', ':status'))
                ->setParameter('status', $filter->status->value)
            ;
        }

        if (null !== $filter->subjectType) {
            $qb
                ->andWhere($qb->expr()->eq('r.subjectType', ':subjectType'))
                ->setParameter('subjectType', $filter->subjectType->value)
            ;
        }

        if (null !== $filter->accountId) {
            $qb
                ->andWhere($qb->expr()->eq('r.accountId', ':accountId'))
                ->setParameter('accountId', (string) $filter->accountId)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'submittedAt' => $qb->orderBy('r.submittedAt', $way),
            default => $qb->orderBy('r.registeredAt', $way),
        };

        return $qb;
    }
}
