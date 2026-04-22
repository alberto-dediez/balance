<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Persistence\Doctrine\Repository;

use App\Property\Domain\Model\Company\Company;
use App\Property\Domain\Model\Company\CompanyFilter;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Security\Domain\Model\Account\AccountId;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.company', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineCompanyRepository implements CompanyRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(Company $company): void
    {
        $this->manager->persist($company);
        $this->manager->flush();
    }

    public function remove(Company $company): void
    {
        $this->manager->remove($company);
        $this->manager->flush();
    }

    public function find(CompanyId $companyId): ?Company
    {
        $qb = $this->createQueryBuilderFromFilter(new CompanyFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('c.id', ':id'),
            )
            ->setParameter('id', (string) $companyId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(CompanyFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('c')
            ->from(Company::class, 'c')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('c.removed'),
                    $qb->expr()->eq('c.removed', ':removed'),
                ),
            )
            ->setParameter('removed', false, Types::BOOLEAN)
        ;

        if (!empty($filter->name)) {
            $qb
                ->andWhere(
                    $qb->expr()->like('c.name', ':name'),
                )
                ->setParameter('name', "%{$filter->name}%")
            ;
        }

        if ($filter->accountId instanceof AccountId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('c.accountId', ':accountId'),
                )
                ->setParameter('accountId', $filter->accountId)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('c.registeredAt', $way),
            default => $qb->orderBy('c.name', $way),
        };

        return $qb;
    }

    /** @return array<Company> */
    public function findByFilter(CompanyFilter $filter): array
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

    public function totalResultsByFilter(CompanyFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT c)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
