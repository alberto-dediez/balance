<?php

namespace App\FoodLoss\Infrastructure\Persistence\Doctrine\Repository;

use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Infrastructure\Persistence\Doctrine\Repository\DoctrineTranslatableRepositoryTrait;
use App\FoodLoss\Domain\Model\Action\Action;
use App\FoodLoss\Domain\Model\Action\ActionFilter;
use App\FoodLoss\Domain\Model\Action\ActionId;
use App\FoodLoss\Domain\Model\Action\ActionRepository;
use App\FoodLoss\Domain\Model\Action\ActionTranslation;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.action', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineActionRepository implements ActionRepository
{
    use DoctrineTranslatableRepositoryTrait;

    private EntityRepository $repository;

    public function __construct(private EntityManagerInterface $manager, private LocaleGetter $localeGetter)
    {
        $this->repository = $manager->getRepository(Action::class);
    }

    public function add(Action $action): void
    {
        $this->getEntityManager()->persist($action);

        $this->addTranslations($action, $this->getEntityManager()->getRepository(ActionTranslation::class));

        $this->getEntityManager()->flush();
    }

    public function remove(Action $action): void
    {
        $this->getEntityManager()->remove($action);
        $this->getEntityManager()->flush();
    }

    public function find(ActionId $actionId): ?Action
    {
        $qb = $this->createQueryBuilderFromFilter(new ActionFilter());

        $action = $qb
            ->andWhere(
                $qb->expr()->eq('a.id', ':id')
            )
            ->setParameter('id', (string) $actionId)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        $this->loadTranslations($action, $this->getEntityManager()->getRepository(ActionTranslation::class));

        return $action;
    }

    private function createQueryBuilderFromFilter(ActionFilter $filter): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('a')
            ->from(Action::class, 'a')
            ->leftJoin(ActionTranslation::class, 'trans', 'WITH', 'trans.translatableId = a.id AND trans.locale = :locale')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('a.removed'),
                    $qb->expr()->eq('a.removed', $qb->expr()->literal(false))
                )
            )
            ->setParameter('locale', $this->localeGetter->locale())
        ;

        if (!empty($filter->description)) {
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->andX(
                            $qb->expr()->like('a.description', ':description'),
                            $qb->expr()->isNull('trans.description')
                        ),
                        $qb->expr()->like('trans.description', ':description')
                    )
                )
                ->setParameter('description', "%{$filter->description}%")
            ;
        }

        if (null !== $filter->priority) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('a.priority', ':priority')
                )
                ->setParameter('priority', $filter->priority->value)
            ;
        }

        $way = $filter->orderWay == 'ASC' ? 'ASC' : 'DESC';

        switch ($filter->orderBy) {
            case 'registeredAt':
                $qb->orderBy('a.registeredAt', $way);
                break;
            case 'updatedAt':
                $qb->orderBy('a.updatedAt', $way);
                break;
            case 'priority':
                $qb->orderBy('a.priority', $way);
                break;
            case 'description':
            default:
                $qb->orderBy('a.description', $way);
                break;
        }

        return $qb;
    }

    public function findByFilter(ActionFilter $filter): array
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        $query = $qb->getQuery();

        if ($filter->limit > 0) {
            $query
                ->setMaxResults($filter->limit)
                ->setFirstResult($filter->page * $filter->limit)
            ;
        }

        $actions = (array) $query->getResult();

        if (count($actions) == 0) {
            return [];
        }

        foreach ($actions as $action) {
            $this->loadTranslations($action, $this->getEntityManager()->getRepository(ActionTranslation::class));
        }

        return $actions;
    }

    public function totalResultsByFilter(ActionFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return $qb->select('COUNT(DISTINCT a)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function getEntityManager()
    {
        return $this->manager;
    }
}
