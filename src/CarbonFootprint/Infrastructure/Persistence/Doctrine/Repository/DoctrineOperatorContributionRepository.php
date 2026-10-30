<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContribution;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionFilter;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionId;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionRepository;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionStatus;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.operator_contribution', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineOperatorContributionRepository implements OperatorContributionRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(OperatorContribution $contribution): void
    {
        $this->manager->persist($contribution);
        $this->manager->flush();
    }

    public function find(OperatorContributionId $id): ?OperatorContribution
    {
        $qb = $this->createQueryBuilderFromFilter(new OperatorContributionFilter());

        return $qb
            ->andWhere($qb->expr()->eq('c.id', ':id'))
            ->setParameter('id', (string) $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByToken(#[\SensitiveParameter] string $token): ?OperatorContribution
    {
        if ('' === $token) {
            return null;
        }

        $qb = $this->createQueryBuilderFromFilter(new OperatorContributionFilter());

        return $qb
            ->andWhere($qb->expr()->eq('c.token', ':token'))
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /** @return list<OperatorContribution> */
    public function findActiveByCompanyCarbonFootprint(CompanyCarbonFootprintId $id): array
    {
        return $this->findByFilter(new OperatorContributionFilter(
            companyCarbonFootprintId: $id,
            limit: 0,
        ));
    }

    public function findActiveByCompanyCarbonFootprintAndProperty(CompanyCarbonFootprintId $id, PropertyId $propertyId): ?OperatorContribution
    {
        return $this->createQueryBuilderFromFilter(new OperatorContributionFilter(
            companyCarbonFootprintId: $id,
            propertyId: $propertyId,
        ))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findLatestByAccountAndProperty(AccountId $accountId, PropertyId $propertyId): ?OperatorContribution
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb->select('c')
            ->from(OperatorContribution::class, 'c')
            ->where($qb->expr()->eq('c.accountId', ':accountId'))
            ->andWhere($qb->expr()->eq('c.propertyId', ':propertyId'))
            ->setParameter('accountId', (string) $accountId)
            ->setParameter('propertyId', (string) $propertyId)
            ->orderBy('c.registeredAt', 'DESC')
            ->addOrderBy('c.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /** @return list<OperatorContribution> */
    public function findByFilter(OperatorContributionFilter $filter): array
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

    public function totalResultsByFilter(OperatorContributionFilter $filter): int
    {
        return (int) $this->createQueryBuilderFromFilter($filter)
            ->select('COUNT(DISTINCT c)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function createQueryBuilderFromFilter(OperatorContributionFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('c')
            ->from(OperatorContribution::class, 'c')
            ->where($qb->expr()->eq('c.removed', $qb->expr()->literal(false)))
        ;

        if ($filter->companyCarbonFootprintId instanceof CompanyCarbonFootprintId) {
            $qb
                ->andWhere($qb->expr()->eq('c.companyCarbonFootprintId', ':companyCarbonFootprintId'))
                ->setParameter('companyCarbonFootprintId', (string) $filter->companyCarbonFootprintId)
            ;
        }

        if ($filter->accountId instanceof AccountId) {
            $qb
                ->andWhere($qb->expr()->eq('c.accountId', ':accountId'))
                ->setParameter('accountId', (string) $filter->accountId)
            ;
        }

        if ($filter->propertyId instanceof PropertyId) {
            $qb
                ->andWhere($qb->expr()->eq('c.propertyId', ':propertyId'))
                ->setParameter('propertyId', (string) $filter->propertyId)
            ;
        }

        if ($filter->status instanceof ProviderContributionStatus) {
            $qb
                ->andWhere($qb->expr()->eq('c.status', ':status'))
                ->setParameter('status', $filter->status->value)
            ;
        }

        $way = 'DESC' === $filter->orderWay ? 'DESC' : 'ASC';
        $qb->orderBy('c.registeredAt', $way)->addOrderBy('c.id', 'ASC');

        return $qb;
    }
}
