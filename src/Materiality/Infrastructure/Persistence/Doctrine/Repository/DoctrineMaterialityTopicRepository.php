<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\Persistence\Doctrine\Repository;

use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopic;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicFilter;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicRepository;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicTranslation;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Infrastructure\Persistence\Doctrine\Repository\DoctrineTranslatableRepositoryTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.materiality_topic', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineMaterialityTopicRepository implements MaterialityTopicRepository
{
    use DoctrineTranslatableRepositoryTrait;

    public function __construct(private EntityManagerInterface $manager) {}

    public function add(MaterialityTopic $materialityTopic): void
    {
        $this->manager->persist($materialityTopic);
        $this->addTranslations($materialityTopic, MaterialityTopicTranslation::class, $this->manager);
        $this->manager->flush();
    }

    public function remove(MaterialityTopic $materialityTopic): void
    {
        $this->manager->remove($materialityTopic);
        $this->manager->flush();
    }

    public function find(MaterialityTopicId $materialityTopicId): ?MaterialityTopic
    {
        $qb = $this->createQueryBuilderFromFilter(new MaterialityTopicFilter());

        return $qb
            ->andWhere($qb->expr()->eq('t.id', ':id'))
            ->setParameter('id', (string) $materialityTopicId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByNumber(int $number): ?MaterialityTopic
    {
        $qb = $this->createQueryBuilderFromFilter(new MaterialityTopicFilter());

        return $qb
            ->andWhere($qb->expr()->eq('t.number', ':number'))
            ->setParameter('number', $number)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /** @return array<MaterialityTopic> */
    public function findByFilter(MaterialityTopicFilter $filter): array
    {
        $query = $this->createQueryBuilderFromFilter($filter)->getQuery();

        if ($filter->limit > 0) {
            $query
                ->setMaxResults($filter->limit)
                ->setFirstResult($filter->page * $filter->limit)
            ;
        }

        return $query->getResult();
    }

    public function totalResultsByFilter(MaterialityTopicFilter $filter): int
    {
        return (int) $this->createQueryBuilderFromFilter($filter)
            ->select('COUNT(DISTINCT t)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /** @return list<MaterialityTopic> */
    public function findActive(): array
    {
        return array_values($this->findByFilter(new MaterialityTopicFilter(active: true, limit: 0)));
    }

    private function createQueryBuilderFromFilter(MaterialityTopicFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('t')
            ->from(MaterialityTopic::class, 't')
            ->leftJoin(MaterialityTopicTranslation::class, 'trans', 'WITH', 'trans.translatable = t AND trans.locale = :locale')
            ->setParameter('locale', LocaleGetter::locale())
        ;

        if (!$filter->includeRemoved) {
            $qb->andWhere($qb->expr()->eq('t.removed', $qb->expr()->literal(false)));
        }

        if (null !== $filter->name && '' !== $filter->name) {
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->andX(
                            $qb->expr()->like('t.name', ':name'),
                            $qb->expr()->isNull('trans.name'),
                        ),
                        $qb->expr()->like('trans.name', ':name'),
                    ),
                )
                ->setParameter('name', '%' . $filter->name . '%')
            ;
        }

        if (null !== $filter->scope) {
            $qb
                ->andWhere($qb->expr()->eq('t.scope', ':scope'))
                ->setParameter('scope', $filter->scope->value)
            ;
        }

        if (null !== $filter->direction) {
            $qb
                ->andWhere($qb->expr()->eq('t.direction', ':direction'))
                ->setParameter('direction', $filter->direction->value)
            ;
        }

        if (null !== $filter->active) {
            $qb
                ->andWhere($qb->expr()->eq('t.active', ':active'))
                ->setParameter('active', $filter->active)
            ;
        }

        if (null !== $filter->standard) {
            $qb
                ->andWhere($qb->expr()->eq('t.standard', ':standard'))
                ->setParameter('standard', $filter->standard)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'name' => $qb->orderBy('t.name', $way),
            'number' => $qb->orderBy('t.number', $way),
            'registeredAt' => $qb->orderBy('t.registeredAt', $way),
            'updatedAt' => $qb->orderBy('t.updatedAt', $way),
            default => $qb->orderBy('t.position', $way)->addOrderBy('t.name', $way),
        };

        $qb->addOrderBy('t.id', 'ASC');

        return $qb;
    }
}
