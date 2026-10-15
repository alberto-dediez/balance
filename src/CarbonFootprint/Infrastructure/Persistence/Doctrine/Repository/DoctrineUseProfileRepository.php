<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\UseProfile\UseProfile;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileFilter;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileId;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileRepository;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileTranslation;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Infrastructure\Persistence\Doctrine\Repository\DoctrineTranslatableRepositoryTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.use_profile', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineUseProfileRepository implements UseProfileRepository
{
    use DoctrineTranslatableRepositoryTrait;

    public function __construct(private EntityManagerInterface $manager) {}

    public function add(UseProfile $useProfile): void
    {
        $this->manager->persist($useProfile);
        $this->addTranslations($useProfile, UseProfileTranslation::class, $this->manager);
        $this->manager->flush();
    }

    public function remove(UseProfile $useProfile): void
    {
        $this->manager->remove($useProfile);
        $this->manager->flush();
    }

    public function find(UseProfileId $useProfileId): ?UseProfile
    {
        $qb = $this->createQueryBuilderFromFilter(new UseProfileFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('up.id', ':id'),
            )
            ->setParameter('id', (string) $useProfileId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(UseProfileFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('up')
            ->from(UseProfile::class, 'up')
            ->leftJoin(UseProfileTranslation::class, 'trans', 'WITH', 'trans.translatable = up AND trans.locale = :locale')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('up.removed'),
                    $qb->expr()->eq('up.removed', $qb->expr()->literal(false)),
                ),
            )
            ->setParameter('locale', LocaleGetter::locale())
        ;

        if (!empty($filter->name)) {
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->andX(
                            $qb->expr()->like('up.name', ':name'),
                            $qb->expr()->isNull('trans.name'),
                        ),
                        $qb->expr()->like('trans.name', ':name'),
                    ),
                )
                ->setParameter('name', "%{$filter->name}%")
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('up.registeredAt', $way),
            'updatedAt' => $qb->orderBy('up.updatedAt', $way),
            default => $qb->orderBy('up.name', $way),
        };

        $qb->addOrderBy('up.id', 'ASC');

        return $qb;
    }

    /** @return array<UseProfile> */
    public function findByFilter(UseProfileFilter $filter): array
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

    public function totalResultsByFilter(UseProfileFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT up)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->manager;
    }
}
