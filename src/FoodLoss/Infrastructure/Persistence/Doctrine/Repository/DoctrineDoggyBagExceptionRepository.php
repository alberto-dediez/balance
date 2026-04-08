<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\Doctrine\Repository;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPoint;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagException;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionFilter;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionId;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionRepository;
use App\Property\Domain\Model\Property\PropertyId;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: DoggyBagExceptionRepository::class, public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineDoggyBagExceptionRepository implements DoggyBagExceptionRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(DoggyBagException $doggyBagException): void
    {
        $this->manager->persist($doggyBagException);
        $this->manager->flush();
    }

    public function remove(DoggyBagException $doggyBagException): void
    {
        $this->manager->remove($doggyBagException);
        $this->manager->flush();
    }

    public function find(DoggyBagExceptionId $doggyBagExceptionId): ?DoggyBagException
    {
        $qb = $this->createQueryBuilderFromFilter(new DoggyBagExceptionFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('e.id', ':id'),
            )
            ->setParameter('id', (string) $doggyBagExceptionId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(DoggyBagExceptionFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('e')
            ->from(DoggyBagException::class, 'e')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('e.removed'),
                    $qb->expr()->eq('e.removed', ':removed'),
                ),
            )
            ->setParameter('removed', false, Types::BOOLEAN)
        ;

        if (!empty($filter->description)) {
            $qb
                ->andWhere(
                    $qb->expr()->like('e.description', ':description'),
                )
                ->setParameter('description', "%{$filter->description}%")
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

        if ($filter->propertyId instanceof PropertyId) {
            $qb
                ->join(CriticalPoint::class, 'cp', 'WITH', 'cp.id = e.criticalPointId')
                ->andWhere(
                    $qb->expr()->eq('cp.propertyId', ':propertyId'),
                )
                ->setParameter('propertyId', (string) $filter->propertyId)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('e.registeredAt', $way),
            default => $qb->orderBy('e.registeredAt', $way),
        };

        return $qb;
    }

    /** @return array<DoggyBagException> */
    public function findByFilter(DoggyBagExceptionFilter $filter): array
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

    public function totalResultsByFilter(DoggyBagExceptionFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT e)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
