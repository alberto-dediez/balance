<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\Doctrine\Repository;

use App\FoodLoss\Domain\Model\Protocol\Protocol;
use App\FoodLoss\Domain\Model\Protocol\ProtocolFilter;
use App\FoodLoss\Domain\Model\Protocol\ProtocolId;
use App\FoodLoss\Domain\Model\Protocol\ProtocolRepository;
use App\Security\Domain\Model\Account\AccountId;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.protocol', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineProtocolRepository implements ProtocolRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(Protocol $protocol): void
    {
        $this->manager->persist($protocol);
        $this->manager->flush();
    }

    public function remove(Protocol $protocol): void
    {
        $this->manager->remove($protocol);
        $this->manager->flush();
    }

    public function find(ProtocolId $protocolId): ?Protocol
    {
        $qb = $this->createQueryBuilderFromFilter(new ProtocolFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('e.id', ':id'),
            )
            ->setParameter('id', (string) $protocolId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(ProtocolFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('e')
            ->from(Protocol::class, 'e')
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

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('e.registeredAt', $way),
            default => $qb->orderBy('e.name', $way),
        };

        return $qb;
    }

    /** @return array<Protocol> */
    public function findByFilter(ProtocolFilter $filter): array
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

    public function totalResultsByFilter(ProtocolFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT e)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
