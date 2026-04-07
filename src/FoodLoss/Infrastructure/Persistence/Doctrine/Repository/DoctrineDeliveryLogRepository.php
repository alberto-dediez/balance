<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\Doctrine\Repository;

use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLog;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogFilter;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogId;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepository;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.delivery_log', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineDeliveryLogRepository implements DeliveryLogRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(DeliveryLog $deliveryLog): void
    {
        $this->manager->persist($deliveryLog);
        $this->manager->flush();
    }

    public function remove(DeliveryLog $deliveryLog): void
    {
        $this->manager->remove($deliveryLog);
        $this->manager->flush();
    }

    public function find(DeliveryLogId $deliveryLogId): ?DeliveryLog
    {
        $qb = $this->createQueryBuilderFromFilter(new DeliveryLogFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('e.id', ':id'),
            )
            ->setParameter('id', (string) $deliveryLogId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(DeliveryLogFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('e')
            ->from(DeliveryLog::class, 'e')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('e.removed'),
                    $qb->expr()->eq('e.removed', ':removed'),
                ),
            )
            ->setParameter('removed', false, Types::BOOLEAN)
        ;

        if ($filter->accountId instanceof AccountId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('e.accountId', ':accountId'),
                )
                ->setParameter('accountId', (string) $filter->accountId)
            ;
        }

        if ($filter->recipientOrganizationId instanceof RecipientOrganizationId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('e.recipientOrganizationId', ':recipientOrganizationId'),
                )
                ->setParameter('recipientOrganizationId', (string) $filter->recipientOrganizationId)
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

        if (!empty($filter->productType)) {
            $qb
                ->andWhere(
                    $qb->expr()->like('e.productType', ':productType'),
                )
                ->setParameter('productType', "%{$filter->productType}%")
            ;
        }

        if ($filter->dateFrom instanceof \DateTimeImmutable) {
            $qb
                ->andWhere(
                    $qb->expr()->gte('e.date', ':dateFrom'),
                )
                ->setParameter('dateFrom', $filter->dateFrom, Types::DATETIME_IMMUTABLE)
            ;
        }

        if ($filter->dateTo instanceof \DateTimeImmutable) {
            $qb
                ->andWhere(
                    $qb->expr()->lte('e.date', ':dateTo'),
                )
                ->setParameter('dateTo', $filter->dateTo, Types::DATETIME_IMMUTABLE)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'date' => $qb->orderBy('e.date', $way),
            'registeredAt' => $qb->orderBy('e.registeredAt', $way),
            default => $qb->orderBy('e.date', $way),
        };

        return $qb;
    }

    /** @return array<DeliveryLog> */
    public function findByFilter(DeliveryLogFilter $filter): array
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

    public function totalResultsByFilter(DeliveryLogFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT e)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
