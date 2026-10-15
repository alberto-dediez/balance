<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRate;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateFilter;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateId;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.exchange_rate', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineExchangeRateRepository implements ExchangeRateRepository
{
    public function __construct(private EntityManagerInterface $manager) {}

    public function add(ExchangeRate $rate): void
    {
        $this->manager->persist($rate);
        $this->manager->flush();
    }

    public function find(ExchangeRateId $id): ?ExchangeRate
    {
        return $this->manager->find(ExchangeRate::class, $id);
    }

    public function findByCurrency(Unit $currency): ?ExchangeRate
    {
        return $this->manager->getRepository(ExchangeRate::class)->findOneBy(['currency' => $currency]);
    }

    /** @return list<ExchangeRate> */
    public function findByFilter(ExchangeRateFilter $filter): array
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

    public function totalResultsByFilter(ExchangeRateFilter $filter): int
    {
        return (int) $this->createQueryBuilderFromFilter($filter)
            ->select('COUNT(DISTINCT r)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function createQueryBuilderFromFilter(ExchangeRateFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('r')->from(ExchangeRate::class, 'r');

        if (null !== $filter->currency) {
            $qb
                ->andWhere($qb->expr()->eq('r.currency', ':currency'))
                ->setParameter('currency', $filter->currency->value)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'date' => $qb->orderBy('r.date', $way),
            'rate' => $qb->orderBy('r.rate', $way),
            default => $qb->orderBy('r.currency', $way),
        };

        $qb->addOrderBy('r.id', 'ASC');

        return $qb;
    }
}
