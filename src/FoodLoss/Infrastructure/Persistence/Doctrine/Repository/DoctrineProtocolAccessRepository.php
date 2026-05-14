<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\Doctrine\Repository;

use App\FoodLoss\Domain\Model\Protocol\ProtocolId;
use App\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccess;
use App\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccessFilter;
use App\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccessId;
use App\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccessRepository;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.protocol_access', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineProtocolAccessRepository implements ProtocolAccessRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(ProtocolAccess $protocolAccess): void
    {
        $this->manager->persist($protocolAccess);
        $this->manager->flush();
    }

    public function find(ProtocolAccessId $protocolAccessId): ?ProtocolAccess
    {
        $qb = $this->createQueryBuilderFromFilter(new ProtocolAccessFilter());

        return $qb
            ->andWhere($qb->expr()->eq('e.id', ':id'))
            ->setParameter('id', (string) $protocolAccessId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /** @return array<ProtocolAccess> */
    public function findByFilter(ProtocolAccessFilter $filter): array
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

    public function totalResultsByFilter(ProtocolAccessFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT e)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /** @return array<ProtocolAccess> */
    public function findLastAccessPerEmployee(ProtocolId $protocolId): array
    {
        $subQb = $this->manager->createQueryBuilder();
        $subQb->select('MAX(sub.accessedAt)')
            ->from(ProtocolAccess::class, 'sub')
            ->where($subQb->expr()->eq('sub.protocolId', ':protocolId'))
            ->groupBy('sub.employeeId')
        ;

        $qb = $this->manager->createQueryBuilder();

        return (array) $qb->select('e')
            ->from(ProtocolAccess::class, 'e')
            ->where($qb->expr()->eq('e.protocolId', ':protocolId'))
            ->andWhere($qb->expr()->in('e.accessedAt', $subQb->getDQL()))
            ->setParameter('protocolId', (string) $protocolId)
            ->orderBy('e.accessedAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /** @return array<ProtocolAccess> */
    public function findHistoryByProtocolAndEmployee(ProtocolId $protocolId, EmployeeId $employeeId): array
    {
        $qb = $this->manager->createQueryBuilder();

        return (array) $qb->select('e')
            ->from(ProtocolAccess::class, 'e')
            ->where($qb->expr()->eq('e.protocolId', ':protocolId'))
            ->andWhere($qb->expr()->eq('e.employeeId', ':employeeId'))
            ->setParameter('protocolId', (string) $protocolId)
            ->setParameter('employeeId', (string) $employeeId)
            ->orderBy('e.accessedAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    private function createQueryBuilderFromFilter(ProtocolAccessFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('e')
            ->from(ProtocolAccess::class, 'e')
        ;

        if (null !== $filter->protocolId) {
            $qb
                ->andWhere($qb->expr()->eq('e.protocolId', ':protocolId'))
                ->setParameter('protocolId', (string) $filter->protocolId)
            ;
        }

        if (null !== $filter->employeeId) {
            $qb
                ->andWhere($qb->expr()->eq('e.employeeId', ':employeeId'))
                ->setParameter('employeeId', (string) $filter->employeeId)
            ;
        }

        if (null !== $filter->accountId) {
            $qb
                ->andWhere($qb->expr()->eq('e.accountId', ':accountId'))
                ->setParameter('accountId', (string) $filter->accountId)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            default => $qb->orderBy('e.accessedAt', $way),
        };

        return $qb;
    }
}
