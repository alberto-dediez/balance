<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Persistence\Doctrine\Repository;

use App\Property\Domain\Model\DistributorAssignment\DistributorAssignment;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignmentFilter;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignmentId;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignmentRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.distributor_assignment', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineDistributorAssignmentRepository implements DistributorAssignmentRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(DistributorAssignment $assignment): void
    {
        $this->manager->persist($assignment);
        $this->manager->flush();
    }

    public function remove(DistributorAssignment $assignment): void
    {
        $this->manager->remove($assignment);
        $this->manager->flush();
    }

    public function find(DistributorAssignmentId $id): ?DistributorAssignment
    {
        $qb = $this->createQueryBuilderFromFilter(new DistributorAssignmentFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('d.id', ':id'),
            )
            ->setParameter('id', (string) $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findForAccount(AccountId $distributorAccountId, AccountId $accountId): ?DistributorAssignment
    {
        $qb = $this->createQueryBuilderFromFilter(new DistributorAssignmentFilter(distributorAccountId: $distributorAccountId, accountId: $accountId));

        return $qb
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult()
        ;
    }

    public function findForProperty(AccountId $distributorAccountId, PropertyId $propertyId): ?DistributorAssignment
    {
        $qb = $this->createQueryBuilderFromFilter(new DistributorAssignmentFilter(distributorAccountId: $distributorAccountId, propertyId: $propertyId));

        return $qb
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult()
        ;
    }

    /** @return list<DistributorAssignment> */
    public function findByFilter(DistributorAssignmentFilter $filter): array
    {
        $query = $this->createQueryBuilderFromFilter($filter)->getQuery();

        if ($filter->limit > 0) {
            $query
                ->setMaxResults($filter->limit)
                ->setFirstResult($filter->page * $filter->limit)
            ;
        }

        /** @var list<DistributorAssignment> $result */
        $result = $query->getResult();

        return $result;
    }

    public function totalResultsByFilter(DistributorAssignmentFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT d)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function createQueryBuilderFromFilter(DistributorAssignmentFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('d')
            ->from(DistributorAssignment::class, 'd')
            ->where(
                $qb->expr()->eq('d.removed', ':removed'),
            )
            ->setParameter('removed', false, Types::BOOLEAN)
        ;

        if ($filter->distributorAccountId instanceof AccountId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('d.distributorAccountId', ':distributorAccountId'),
                )
                ->setParameter('distributorAccountId', (string) $filter->distributorAccountId)
            ;
        }

        if ($filter->propertyId instanceof PropertyId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('d.propertyId', ':propertyId'),
                )
                ->setParameter('propertyId', (string) $filter->propertyId)
            ;
        }

        $this->applyTarget($qb, $filter);

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('d.registeredAt', $way),
            default => $qb->orderBy('d.registeredAt', 'DESC'),
        };

        return $qb;
    }

    private function applyTarget(QueryBuilder $qb, DistributorAssignmentFilter $filter): void
    {
        $accountCondition = null;

        if ($filter->accountId instanceof AccountId) {
            $accountCondition = $qb->expr()->eq('d.accountId', ':accountId');
            $qb->setParameter('accountId', (string) $filter->accountId);
        }

        $propertiesCondition = null;

        if (null !== $filter->propertyIds && [] !== $filter->propertyIds) {
            $propertiesCondition = $qb->expr()->in('d.propertyId', ':propertyIds');
            $qb->setParameter('propertyIds', array_map(static fn(PropertyId $propertyId): string => (string) $propertyId, $filter->propertyIds));
        }

        if (null !== $accountCondition && null !== $propertiesCondition) {
            $qb->andWhere($qb->expr()->orX($accountCondition, $propertiesCondition));

            return;
        }

        if (null !== $accountCondition) {
            $qb->andWhere($accountCondition);

            return;
        }

        if (null !== $propertiesCondition) {
            $qb->andWhere($propertiesCondition);

            return;
        }

        if ([] === $filter->propertyIds) {
            $qb->andWhere('1 = 0');
        }
    }
}
