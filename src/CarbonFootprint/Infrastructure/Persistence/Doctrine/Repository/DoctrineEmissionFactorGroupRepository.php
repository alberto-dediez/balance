<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupFilter;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupTranslation;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Infrastructure\Persistence\Doctrine\Repository\DoctrineTranslatableRepositoryTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.emission_factor_group', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineEmissionFactorGroupRepository implements EmissionFactorGroupRepository
{
    use DoctrineTranslatableRepositoryTrait;

    public function __construct(private EntityManagerInterface $manager) {}

    public function add(EmissionFactorGroup $emissionFactorGroup): void
    {
        $this->manager->persist($emissionFactorGroup);
        $this->addTranslations($emissionFactorGroup, EmissionFactorGroupTranslation::class, $this->manager);
        $this->manager->flush();
    }

    public function remove(EmissionFactorGroup $emissionFactorGroup): void
    {
        $this->manager->remove($emissionFactorGroup);
        $this->manager->flush();
    }

    public function find(EmissionFactorGroupId $emissionFactorGroupId): ?EmissionFactorGroup
    {
        $qb = $this->createQueryBuilderFromFilter(new EmissionFactorGroupFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('e.id', ':id'),
            )
            ->setParameter('id', (string) $emissionFactorGroupId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findDefault(): ?EmissionFactorGroup
    {
        $qb = $this->createQueryBuilderFromFilter(new EmissionFactorGroupFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('e.default', ':default'),
            )
            ->setParameter('default', true)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(EmissionFactorGroupFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('e')
            ->from(EmissionFactorGroup::class, 'e')
            ->leftJoin(EmissionFactorGroupTranslation::class, 'trans', 'WITH', 'trans.translatable = e AND trans.locale = :locale')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('e.removed'),
                    $qb->expr()->eq('e.removed', $qb->expr()->literal(false)),
                ),
            )
            ->setParameter('locale', LocaleGetter::locale())
        ;

        if (!empty($filter->name)) {
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->andX(
                            $qb->expr()->like('e.name', ':name'),
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
            'registeredAt' => $qb->orderBy('e.registeredAt', $way),
            'updatedAt' => $qb->orderBy('e.updatedAt', $way),
            default => $qb->orderBy('e.name', $way),
        };

        return $qb;
    }

    /** @return array<EmissionFactorGroup> */
    public function findByFilter(EmissionFactorGroupFilter $filter): array
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

    public function totalResultsByFilter(EmissionFactorGroupFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT e)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->manager;
    }
}
