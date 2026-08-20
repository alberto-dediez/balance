<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Persistence\Doctrine\Repository;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Branding\Branding;
use App\Security\Domain\Model\Branding\BrandingFilter;
use App\Security\Domain\Model\Branding\BrandingId;
use App\Security\Domain\Model\Branding\BrandingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.branding', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineBrandingRepository implements BrandingRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(Branding $branding): void
    {
        $this->manager->persist($branding);
        $this->manager->flush();
    }

    public function remove(Branding $branding): void
    {
        $this->manager->remove($branding);
        $this->manager->flush();
    }

    public function find(BrandingId $brandingId): ?Branding
    {
        $qb = $this->createQueryBuilderFromFilter(new BrandingFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('b.id', ':id'),
            )
            ->setParameter('id', $brandingId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByAccountId(AccountId $accountId): ?Branding
    {
        $qb = $this->createQueryBuilderFromFilter(new BrandingFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('b.accountId', ':accountId'),
            )
            ->setParameter('accountId', $accountId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(BrandingFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('b')
            ->from(Branding::class, 'b')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('b.removed'),
                    $qb->expr()->eq('b.removed', ':removed'),
                ),
            )
            ->setParameter('removed', false, Types::BOOLEAN)
        ;

        if ($filter->accountId instanceof AccountId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('b.accountId', ':accountId'),
                )
                ->setParameter('accountId', $filter->accountId)
            ;
        }

        if (!in_array($filter->themeSlug, [null, '', '0'], true)) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('b.themeSlug', ':themeSlug'),
                )
                ->setParameter('themeSlug', $filter->themeSlug)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'themeSlug' => $qb->orderBy('b.themeSlug', $way),
            default => $qb->orderBy('b.registeredAt', $way),
        };

        return $qb;
    }

    /**
     * @return list<Branding>
     */
    public function findByFilter(BrandingFilter $filter): array
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

    public function totalResultsByFilter(BrandingFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        $count = $qb->select('COUNT(DISTINCT b)')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return intval($count);
    }
}
