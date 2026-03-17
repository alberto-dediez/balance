<?php

namespace App\FoodLoss\Infrastructure\Persistence\Doctrine\Repository;

use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Infrastructure\Persistence\Doctrine\Repository\DoctrineTranslatableRepositoryTrait;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategory;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryFilter;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepository;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryTranslation;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.service.category', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineServiceCategoryRepository implements ServiceCategoryRepository
{
    use DoctrineTranslatableRepositoryTrait;

    private EntityRepository $repository;

    public function __construct(private EntityManagerInterface $manager, private LocaleGetter $localeGetter)
    {
        $this->repository = $manager->getRepository(ServiceCategory::class);
    }

    public function add(ServiceCategory $serviceCategory): void
    {
        $this->getEntityManager()->persist($serviceCategory);

        $this->addTranslations($serviceCategory, $this->getEntityManager()->getRepository(ServiceCategoryTranslation::class));

        $this->getEntityManager()->flush();
    }

    public function remove(ServiceCategory $serviceCategory): void
    {
        $this->getEntityManager()->remove($serviceCategory);
        $this->getEntityManager()->flush();
    }

    public function find(ServiceCategoryId $serviceCategoryId): ?ServiceCategory
    {
        $qb = $this->createQueryBuilderFromFilter(new ServiceCategoryFilter());

        $serviceCategory = $qb
            ->andWhere(
                $qb->expr()->eq('s.id', ':id')
            )
            ->setParameter('id', (string) $serviceCategoryId)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        $this->loadTranslations($serviceCategory, $this->getEntityManager()->getRepository(ServiceCategoryTranslation::class));

        return $serviceCategory;
    }

    private function createQueryBuilderFromFilter(ServiceCategoryFilter $filter): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('s')
            ->from(ServiceCategory::class, 's')
            ->leftJoin(ServiceCategoryTranslation::class, 'trans', 'WITH', 'trans.translatableId = s.id AND trans.locale = :locale')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('s.removed'),
                    $qb->expr()->eq('s.removed', $qb->expr()->literal(false))
                )
            )
            ->setParameter('locale', $this->localeGetter->locale())
        ;

        if (!empty($filter->name)) {
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->andX(
                            $qb->expr()->like('s.name', ':name'),
                            $qb->expr()->isNull('trans.name')
                        ),
                        $qb->expr()->like('trans.name', ':name')
                    )
                )
                ->setParameter('name', "%{$filter->name}%")
            ;
        }

        $way = $filter->orderWay == 'ASC' ? 'ASC' : 'DESC';

        switch ($filter->orderBy) {
            case 'registeredAt':
                $qb->orderBy('s.registeredAt', $way);
                break;
            case 'updatedAt':
                $qb->orderBy('s.updatedAt', $way);
                break;
            case 'name':
            default:
                $qb->orderBy('s.name', $way);
                break;
        }

        return $qb;
    }

    public function findByFilter(ServiceCategoryFilter $filter): array
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        $query = $qb->getQuery();

        if ($filter->limit > 0) {
            $query
                ->setMaxResults($filter->limit)
                ->setFirstResult($filter->page * $filter->limit)
            ;
        }

        $serviceCategorys = (array) $query->getResult();

        if (count($serviceCategorys) == 0) {
            return [];
        }

        foreach ($serviceCategorys as $serviceCategory) {
            $this->loadTranslations($serviceCategory, $this->getEntityManager()->getRepository(ServiceCategoryTranslation::class));
        }

        return $serviceCategorys;
    }

    public function totalResultsByFilter(ServiceCategoryFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return $qb->select('COUNT(DISTINCT s)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function getEntityManager()
    {
        return $this->manager;
    }
}
