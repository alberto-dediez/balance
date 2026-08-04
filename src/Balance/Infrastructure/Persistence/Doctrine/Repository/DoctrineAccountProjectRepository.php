<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Persistence\Doctrine\Repository;

use App\Balance\Domain\Model\AccountProject\AccountProject;
use App\Balance\Domain\Model\AccountProject\AccountProjectFilter;
use App\Balance\Domain\Model\AccountProject\AccountProjectId;
use App\Balance\Domain\Model\AccountProject\AccountProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.account_project', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineAccountProjectRepository implements AccountProjectRepository
{
    public function __construct(private EntityManagerInterface $manager) {}

    public function add(AccountProject $accountProject): void
    {
        $this->manager->persist($accountProject);
        $this->manager->flush();
    }

    public function remove(AccountProject $accountProject): void
    {
        $this->manager->remove($accountProject);
        $this->manager->flush();
    }

    public function find(AccountProjectId $accountProjectId): ?AccountProject
    {
        $qb = $this->createQueryBuilderFromFilter(new AccountProjectFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('ap.id', ':id'),
            )
            ->setParameter('id', (string) $accountProjectId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(AccountProjectFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('ap')
            ->from(AccountProject::class, 'ap')
        ;

        if (null !== $filter->accountId) {
            $qb
                ->andWhere($qb->expr()->eq('ap.accountId', ':accountId'))
                ->setParameter('accountId', (string) $filter->accountId)
            ;
        }

        if (null !== $filter->projectId) {
            $qb
                ->andWhere($qb->expr()->eq('ap.projectId', ':projectId'))
                ->setParameter('projectId', (string) $filter->projectId)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'selectedAt' => $qb->orderBy('ap.selectedAt', $way),
            default => $qb->orderBy('ap.selectedAt', $way),
        };

        $qb->addOrderBy('ap.id', 'ASC');

        return $qb;
    }

    /** @return array<AccountProject> */
    public function findByFilter(AccountProjectFilter $filter): array
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        $query = $qb->getQuery();

        if ($filter->limit > 0) {
            $query
                ->setMaxResults($filter->limit)
                ->setFirstResult($filter->page * $filter->limit)
            ;
        }

        return $query->getResult();
    }

    public function totalResultsByFilter(AccountProjectFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT ap)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
