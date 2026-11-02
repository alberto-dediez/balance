<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\Persistence\Doctrine\Repository;

use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessment;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentFilter;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentId;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentRepository;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentSubjectType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.materiality_assessment', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineMaterialityAssessmentRepository implements MaterialityAssessmentRepository
{
    public function __construct(private EntityManagerInterface $manager) {}

    public function add(MaterialityAssessment $materialityAssessment): void
    {
        $this->manager->persist($materialityAssessment);
        $this->manager->flush();
    }

    public function remove(MaterialityAssessment $materialityAssessment): void
    {
        $this->manager->remove($materialityAssessment);
        $this->manager->flush();
    }

    public function find(MaterialityAssessmentId $materialityAssessmentId): ?MaterialityAssessment
    {
        $qb = $this->createQueryBuilderFromFilter(new MaterialityAssessmentFilter());

        return $qb
            ->andWhere($qb->expr()->eq('a.id', ':id'))
            ->setParameter('id', (string) $materialityAssessmentId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findCurrentByCarbonFootprint(MaterialityAssessmentSubjectType $subjectType, string $carbonFootprintId): ?MaterialityAssessment
    {
        return $this->createQueryBuilderFromFilter(new MaterialityAssessmentFilter(subjectType: $subjectType, carbonFootprintId: $carbonFootprintId))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /** @return array<MaterialityAssessment> */
    public function findByFilter(MaterialityAssessmentFilter $filter): array
    {
        $query = $this->createQueryBuilderFromFilter($filter)->getQuery();

        if ($filter->limit > 0) {
            $query
                ->setMaxResults($filter->limit)
                ->setFirstResult($filter->page * $filter->limit)
            ;
        }

        return $query->getResult();
    }

    public function totalResultsByFilter(MaterialityAssessmentFilter $filter): int
    {
        return (int) $this->createQueryBuilderFromFilter($filter)
            ->select('COUNT(a)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function createQueryBuilderFromFilter(MaterialityAssessmentFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('a')->from(MaterialityAssessment::class, 'a');

        if (!$filter->includeRemoved) {
            $qb->andWhere($qb->expr()->eq('a.removed', $qb->expr()->literal(false)));
        }

        if (null !== $filter->subjectType) {
            $qb
                ->andWhere($qb->expr()->eq('a.subjectType', ':subjectType'))
                ->setParameter('subjectType', $filter->subjectType->value)
            ;
        }

        if (null !== $filter->carbonFootprintId) {
            $qb
                ->andWhere($qb->expr()->eq('a.carbonFootprintId', ':carbonFootprintId'))
                ->setParameter('carbonFootprintId', $filter->carbonFootprintId)
            ;
        }

        if (null !== $filter->accountId) {
            $qb
                ->andWhere($qb->expr()->eq('a.accountId', ':accountId'))
                ->setParameter('accountId', (string) $filter->accountId)
            ;
        }

        if (null !== $filter->year) {
            $qb
                ->andWhere($qb->expr()->eq('a.year', ':year'))
                ->setParameter('year', $filter->year)
            ;
        }

        if (null !== $filter->status) {
            $qb
                ->andWhere($qb->expr()->eq('a.status', ':status'))
                ->setParameter('status', $filter->status->value)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'year' => $qb->orderBy('a.year', $way),
            'registeredAt' => $qb->orderBy('a.registeredAt', $way),
            'completedAt' => $qb->orderBy('a.completedAt', $way),
            default => $qb->orderBy('a.registeredAt', 'DESC'),
        };

        $qb->addOrderBy('a.id', 'ASC');

        return $qb;
    }
}
