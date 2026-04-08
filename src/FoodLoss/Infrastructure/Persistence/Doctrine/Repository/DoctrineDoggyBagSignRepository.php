<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\Doctrine\Repository;

use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSign;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignFilter;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignId;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.doggy_bag_sign', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineDoggyBagSignRepository implements DoggyBagSignRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(DoggyBagSign $doggyBagSign): void
    {
        $this->manager->persist($doggyBagSign);
        $this->manager->flush();
    }

    public function remove(DoggyBagSign $doggyBagSign): void
    {
        $this->manager->remove($doggyBagSign);
        $this->manager->flush();
    }

    public function find(DoggyBagSignId $doggyBagSignId): ?DoggyBagSign
    {
        $qb = $this->createQueryBuilderFromFilter(new DoggyBagSignFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('e.id', ':id'),
            )
            ->setParameter('id', (string) $doggyBagSignId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(DoggyBagSignFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('e')
            ->from(DoggyBagSign::class, 'e')
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

        if ($filter->accountId instanceof AccountId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('e.accountId', ':accountId'),
                )
                ->setParameter('accountId', (string) $filter->accountId)
            ;
        }

        if ($filter->propertyId instanceof PropertyId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('e.propertyId', ':propertyId'),
                )
                ->setParameter('propertyId', (string) $filter->propertyId)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('e.registeredAt', $way),
            default => $qb->orderBy('e.name', $way),
        };

        return $qb;
    }

    /** @return array<DoggyBagSign> */
    public function findByFilter(DoggyBagSignFilter $filter): array
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

    public function totalResultsByFilter(DoggyBagSignFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT e)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
