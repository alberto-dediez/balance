<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Persistence\Doctrine\Repository;

use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfiguration;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationFilter;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationId;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.alertConfiguration', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineAlertConfigurationRepository implements AlertConfigurationRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(AlertConfiguration $config): void
    {
        $this->manager->persist($config);
        $this->manager->flush();
    }

    public function save(AlertConfiguration $config): void
    {
        $this->manager->persist($config);
        $this->manager->flush();
    }

    public function remove(AlertConfiguration $config): void
    {
        $this->manager->remove($config);
        $this->manager->flush();
    }

    public function find(AlertConfigurationId $id): ?AlertConfiguration
    {
        $qb = $this->createQueryBuilderFromFilter(new AlertConfigurationFilter());

        return $qb
            ->andWhere($qb->expr()->eq('ac.id', ':id'))
            ->setParameter('id', (string) $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByAccount(AccountId $accountId): ?AlertConfiguration
    {
        $qb = $this->createQueryBuilderFromFilter(new AlertConfigurationFilter());

        return $qb
            ->andWhere($qb->expr()->eq('ac.accountId', ':accountId'))
            ->setParameter('accountId', $accountId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(AlertConfigurationFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('ac')
            ->from(AlertConfiguration::class, 'ac')
        ;

        if ($filter->accountId instanceof AccountId) {
            $qb
                ->andWhere($qb->expr()->eq('ac.accountId', ':accountId'))
                ->setParameter('accountId', $filter->accountId)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('ac.registeredAt', $way),
            default => $qb->orderBy('ac.registeredAt', $way),
        };

        return $qb;
    }

    /** @return array<AlertConfiguration> */
    public function findByFilter(AlertConfigurationFilter $filter): array
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

    public function totalResultsByFilter(AlertConfigurationFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT ac)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
