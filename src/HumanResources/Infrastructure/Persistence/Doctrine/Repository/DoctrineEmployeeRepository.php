<?php

declare(strict_types=1);

namespace App\HumanResources\Infrastructure\Persistence\Doctrine\Repository;

use App\HumanResources\Domain\Model\Employee\Employee;
use App\HumanResources\Domain\Model\Employee\EmployeeFilter;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\Security\Domain\Model\User\UserId;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.employee', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineEmployeeRepository implements EmployeeRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(Employee $employee): void
    {
        $this->manager->persist($employee);
        $this->manager->flush();
    }

    public function remove(Employee $employee): void
    {
        $this->manager->remove($employee);
        $this->manager->flush();
    }

    public function find(EmployeeId $employeeId): ?Employee
    {
        $qb = $this->createQueryBuilderFromFilter(new EmployeeFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('e.id', ':id'),
            )
            ->setParameter('id', (string) $employeeId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByUserId(UserId $userId): ?Employee
    {
        $results = $this->findByFilter(new EmployeeFilter(userId: $userId, limit: 1));

        return $results[0] ?? null;
    }

    private function createQueryBuilderFromFilter(EmployeeFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('e')
            ->from(Employee::class, 'e')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('e.removed'),
                    $qb->expr()->eq('e.removed', ':removed'),
                ),
            )
            ->setParameter('removed', false, Types::BOOLEAN)
        ;

        if (!empty($filter->name)) {
            $qb
                ->andWhere(
                    $qb->expr()->like('e.name', ':name'),
                )
                ->setParameter('name', "%{$filter->name}%")
            ;
        }

        if (!empty($filter->email)) {
            $qb
                ->andWhere(
                    $qb->expr()->like('e.email', ':email'),
                )
                ->setParameter('email', "%{$filter->email}%")
            ;
        }

        if (null !== $filter->accountId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('e.accountId', ':accountId'),
                )
                ->setParameter('accountId', (string) $filter->accountId)
            ;
        }

        if (null !== $filter->userId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('e.userId', ':userId'),
                )
                ->setParameter('userId', (string) $filter->userId)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('e.registeredAt', $way),
            default => $qb->orderBy('e.name', $way),
        };

        return $qb;
    }

    /** @return array<Employee> */
    public function findByFilter(EmployeeFilter $filter): array
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

    public function totalResultsByFilter(EmployeeFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT e)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
