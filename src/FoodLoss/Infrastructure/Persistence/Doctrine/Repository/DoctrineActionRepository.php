<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\Doctrine\Repository;

use App\FoodLoss\Domain\Model\Action\Priority;
use App\FoodLoss\Domain\Model\Action\Action;
use App\FoodLoss\Domain\Model\Action\ActionFilter;
use App\FoodLoss\Domain\Model\Action\ActionId;
use App\FoodLoss\Domain\Model\Action\ActionRepository;
use App\FoodLoss\Domain\Model\Action\ActionTranslation;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Infrastructure\Persistence\Doctrine\Repository\DoctrineTranslatableRepositoryTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.action', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineActionRepository implements ActionRepository
{
    use DoctrineTranslatableRepositoryTrait;

    public function __construct(private EntityManagerInterface $manager) {}

    public function add(Action $action): void
    {
        $this->manager->persist($action);

        $this->addTranslations($action, ActionTranslation::class, $this->manager);

        $this->manager->flush();
    }

    public function remove(Action $action): void
    {
        $this->manager->remove($action);
        $this->manager->flush();
    }

    public function find(ActionId $actionId): ?Action
    {
        $qb = $this->createQueryBuilderFromFilter(new ActionFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('a.id', ':id'),
            )
            ->setParameter('id', (string) $actionId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function createQueryBuilderFromFilter(ActionFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('a')
            ->from(Action::class, 'a')
            ->leftJoin(ActionTranslation::class, 'trans', 'WITH', 'trans.translatable = a AND trans.locale = :locale')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('a.removed'),
                    $qb->expr()->eq('a.removed', $qb->expr()->literal(false)),
                ),
            )
            ->setParameter('locale', LocaleGetter::locale())
        ;

        if (!empty($filter->description)) {
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->andX(
                            $qb->expr()->like('a.description', ':description'),
                            $qb->expr()->isNull('trans.description'),
                        ),
                        $qb->expr()->like('trans.description', ':description'),
                    ),
                )
                ->setParameter('description', "%{$filter->description}%")
            ;
        }

        if ($filter->priority instanceof Priority) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('a.priority', ':priority'),
                )
                ->setParameter('priority', $filter->priority->value)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('a.registeredAt', $way),
            'updatedAt' => $qb->orderBy('a.updatedAt', $way),
            'priority' => $qb->orderBy('a.priority', $way),
            default => $qb->orderBy('a.description', $way),
        };

        return $qb;
    }

    /** @return array<Action> */
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

        return $query->getResult();
    }

    public function totalResultsByFilter(ActionFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT a)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
