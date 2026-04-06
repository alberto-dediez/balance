<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\Doctrine\Repository;

use App\FoodLoss\Domain\Model\Action\Priority;
use App\FoodLoss\Domain\Model\Reason\Reason;
use App\FoodLoss\Domain\Model\Reason\ReasonFilter;
use App\FoodLoss\Domain\Model\Reason\ReasonId;
use App\FoodLoss\Domain\Model\Reason\ReasonRepository;
use App\FoodLoss\Domain\Model\Reason\ReasonTranslation;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Infrastructure\Persistence\Doctrine\Repository\DoctrineTranslatableRepositoryTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.reason', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineReasonRepository implements ReasonRepository
{
    use DoctrineTranslatableRepositoryTrait;

    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(Reason $reason): void
    {
        $this->manager->persist($reason);
        $this->addTranslations($reason, ReasonTranslation::class, $this->manager);
        $this->manager->flush();
    }

    public function remove(Reason $reason): void
    {
        $this->manager->remove($reason);
        $this->manager->flush();
    }

    public function find(ReasonId $reasonId): ?Reason
    {
        $qb = $this->createQueryBuilderFromFilter(new ReasonFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('e.id', ':id'),
            )
            ->setParameter('id', (string) $reasonId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(ReasonFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('e')
            ->from(Reason::class, 'e')
            ->leftJoin(ReasonTranslation::class, 'trans', 'WITH', 'trans.translatable = e AND trans.locale = :locale')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('e.removed'),
                    $qb->expr()->eq('e.removed', ':removed'),
                ),
            )
            ->setParameter('removed', false, Types::BOOLEAN)
            ->setParameter('locale', LocaleGetter::locale())
        ;

        if (!empty($filter->description)) {
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->andX(
                            $qb->expr()->like('e.description', ':description'),
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
                    $qb->expr()->eq('e.priority', ':priority'),
                )
                ->setParameter('priority', $filter->priority->value)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('e.registeredAt', $way),
            'priority' => $qb->orderBy('e.priority', $way),
            default => $qb->orderBy('e.description', $way),
        };

        return $qb;
    }

    /** @return array<Reason> */
    public function findByFilter(ReasonFilter $filter): array
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

    public function totalResultsByFilter(ReasonFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT e)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
