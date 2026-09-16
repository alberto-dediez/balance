<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintActivity;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintFilter;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintStatus;
use App\Property\Domain\Model\Company\CompanyId;
use App\Security\Domain\Model\Account\AccountId;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.company_carbon_footprint', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineCompanyCarbonFootprintRepository implements CompanyCarbonFootprintRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(CompanyCarbonFootprint $companyCarbonFootprint): void
    {
        $this->manager->persist($companyCarbonFootprint);
        $this->manager->flush();
    }

    public function remove(CompanyCarbonFootprint $companyCarbonFootprint): void
    {
        $this->manager->remove($companyCarbonFootprint);
        $this->manager->flush();
    }

    public function find(CompanyCarbonFootprintId $companyCarbonFootprintId): ?CompanyCarbonFootprint
    {
        $qb = $this->createQueryBuilderFromFilter(new CompanyCarbonFootprintFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('e.id', ':id'),
            )
            ->setParameter('id', (string) $companyCarbonFootprintId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByCompanyIdAndYear(CompanyId $companyId, int $year): ?CompanyCarbonFootprint
    {
        $qb = $this->createQueryBuilderFromFilter(new CompanyCarbonFootprintFilter());

        return $qb
            ->andWhere($qb->expr()->eq('e.companyId', ':companyId'))
            ->andWhere($qb->expr()->eq('e.year', ':year'))
            ->setParameter('companyId', (string) $companyId)
            ->setParameter('year', $year)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /** @return array<CompanyCarbonFootprint> */
    public function findByCompanyId(CompanyId $companyId): array
    {
        $qb = $this->createQueryBuilderFromFilter(new CompanyCarbonFootprintFilter());

        return (array) $qb
            ->andWhere($qb->expr()->eq('e.companyId', ':companyId'))
            ->setParameter('companyId', (string) $companyId)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByActivityId(ActivityId $activityId): ?CompanyCarbonFootprint
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb
            ->select('e')
            ->from(CompanyCarbonFootprint::class, 'e')
            ->join(CompanyCarbonFootprintActivity::class, 'a', 'WITH', 'a.companyCarbonFootprint = e')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('e.removed'),
                    $qb->expr()->eq('e.removed', ':removed'),
                ),
            )
            ->andWhere($qb->expr()->eq('a.activityId', ':activityId'))
            ->setParameter('removed', false, Types::BOOLEAN)
            ->setParameter('activityId', (string) $activityId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(CompanyCarbonFootprintFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('e')
            ->from(CompanyCarbonFootprint::class, 'e')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('e.removed'),
                    $qb->expr()->eq('e.removed', ':removed'),
                ),
            )
            ->setParameter('removed', false, Types::BOOLEAN)
        ;

        if ($filter->companyId instanceof CompanyId) {
            $qb
                ->andWhere($qb->expr()->eq('e.companyId', ':filterCompanyId'))
                ->setParameter('filterCompanyId', $filter->companyId)
            ;
        }

        if (null !== $filter->year) {
            $qb
                ->andWhere($qb->expr()->eq('e.year', ':filterYear'))
                ->setParameter('filterYear', $filter->year)
            ;
        }

        if ($filter->status instanceof OrganizationalCarbonFootprintStatus) {
            $qb
                ->andWhere($qb->expr()->eq('e.status', ':filterStatus'))
                ->setParameter('filterStatus', $filter->status->value)
            ;
        }

        if ($filter->accountId instanceof AccountId) {
            $qb
                ->andWhere($qb->expr()->eq('e.accountId', ':accountId'))
                ->setParameter('accountId', $filter->accountId)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'year' => $qb->orderBy('e.year', $way),
            default => $qb->orderBy('e.registeredAt', $way),
        };

        $qb->addOrderBy('e.id', 'ASC');

        return $qb;
    }

    /** @return array<CompanyCarbonFootprint> */
    public function findByFilter(CompanyCarbonFootprintFilter $filter): array
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        $query = $qb->getQuery();

        if ($filter->limit > 0) {
            $query
                ->setMaxResults($filter->limit)
                ->setFirstResult($filter->page * $filter->limit)
            ;
        }

        return (array) $query->getResult();
    }

    public function totalResultsByFilter(CompanyCarbonFootprintFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT e)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
