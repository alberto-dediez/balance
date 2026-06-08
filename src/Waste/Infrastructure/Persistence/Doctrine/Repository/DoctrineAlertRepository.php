<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Persistence\Doctrine\Repository;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\Alert\Alert;
use App\Waste\Domain\Model\Alert\AlertFilter;
use App\Waste\Domain\Model\Alert\AlertId;
use App\Waste\Domain\Model\Alert\AlertPriority;
use App\Waste\Domain\Model\Alert\AlertRepository;
use App\Waste\Domain\Model\Alert\AlertStatus;
use App\Waste\Domain\Model\Alert\AlertType;
use App\Waste\Domain\Model\Waste\WasteId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.alert', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineAlertRepository implements AlertRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(Alert $alert): void
    {
        $this->manager->persist($alert);
        $this->manager->flush();
    }

    /**
     * @param array<Alert> $alerts
     */
    public function addMultiple(array $alerts): void
    {
        if (0 === count($alerts)) {
            return;
        }

        foreach ($alerts as $alert) {
            $this->manager->persist($alert);
        }

        $this->manager->flush();
    }

    public function find(AlertId $alertId): ?Alert
    {
        $qb = $this->createQueryBuilderFromFilter(new AlertFilter(status: null));

        return $qb
            ->andWhere(
                $qb->expr()->eq('a.id', ':id'),
            )
            ->setParameter('id', (string) $alertId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(AlertFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('a')
            ->from(Alert::class, 'a')
        ;

        if ($filter->accountId instanceof AccountId) {
            $qb
                ->andWhere($qb->expr()->eq('a.accountId', ':accountId'))
                ->setParameter('accountId', $filter->accountId)
            ;
        }

        if ($filter->propertyId instanceof PropertyId) {
            $qb
                ->andWhere($qb->expr()->eq('a.propertyId', ':propertyId'))
                ->setParameter('propertyId', $filter->propertyId)
            ;
        }

        if ($filter->wasteId instanceof WasteId) {
            $qb
                ->andWhere($qb->expr()->eq('a.wasteId', ':wasteId'))
                ->setParameter('wasteId', $filter->wasteId)
            ;
        }

        if ($filter->type instanceof AlertType) {
            $qb
                ->andWhere($qb->expr()->eq('a.type', ':type'))
                ->setParameter('type', $filter->type->value)
            ;
        }

        if ($filter->status instanceof AlertStatus) {
            $qb
                ->andWhere($qb->expr()->eq('a.status', ':status'))
                ->setParameter('status', $filter->status->value)
            ;
        }

        if ($filter->priority instanceof AlertPriority) {
            $qb
                ->andWhere($qb->expr()->eq('a.priority', ':priority'))
                ->setParameter('priority', $filter->priority->value)
            ;
        }

        if ($filter->from instanceof \DateTimeImmutable) {
            $qb
                ->andWhere($qb->expr()->gte('a.detectedAt', ':from'))
                ->setParameter('from', $filter->from->format('Y-m-d'))
            ;
        }

        if ($filter->to instanceof \DateTimeImmutable) {
            $qb
                ->andWhere($qb->expr()->lte('a.detectedAt', ':to'))
                ->setParameter('to', $filter->to->format('Y-m-d'))
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'detectedAt' => $qb->orderBy('a.detectedAt', $way),
            default => $qb->orderBy('a.detectedAt', $way),
        };

        return $qb;
    }

    /** @return array<Alert> */
    public function findByFilter(AlertFilter $filter): array
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

    public function totalResultsByFilter(AlertFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT a)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
