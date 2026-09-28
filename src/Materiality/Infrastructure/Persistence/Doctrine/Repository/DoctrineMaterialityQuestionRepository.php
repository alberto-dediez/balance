<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\Persistence\Doctrine\Repository;

use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestion;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionFilter;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionId;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionRepository;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionTranslation;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Infrastructure\Persistence\Doctrine\Repository\DoctrineTranslatableRepositoryTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.materiality_question', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineMaterialityQuestionRepository implements MaterialityQuestionRepository
{
    use DoctrineTranslatableRepositoryTrait;

    public function __construct(private EntityManagerInterface $manager) {}

    public function add(MaterialityQuestion $materialityQuestion): void
    {
        $this->manager->persist($materialityQuestion);
        $this->addTranslations($materialityQuestion, MaterialityQuestionTranslation::class, $this->manager);
        $this->manager->flush();
    }

    public function remove(MaterialityQuestion $materialityQuestion): void
    {
        $this->manager->remove($materialityQuestion);
        $this->manager->flush();
    }

    public function find(MaterialityQuestionId $materialityQuestionId): ?MaterialityQuestion
    {
        $qb = $this->createQueryBuilderFromFilter(new MaterialityQuestionFilter());

        return $qb
            ->andWhere($qb->expr()->eq('q.id', ':id'))
            ->setParameter('id', (string) $materialityQuestionId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /** @return array<MaterialityQuestion> */
    public function findByFilter(MaterialityQuestionFilter $filter): array
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

    public function totalResultsByFilter(MaterialityQuestionFilter $filter): int
    {
        return (int) $this->createQueryBuilderFromFilter($filter)
            ->select('COUNT(DISTINCT q)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /** @return list<MaterialityQuestion> */
    public function findByTopicId(MaterialityTopicId $topicId): array
    {
        return array_values($this->findByFilter(new MaterialityQuestionFilter(topicId: $topicId, limit: 0)));
    }

    private function createQueryBuilderFromFilter(MaterialityQuestionFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('q')
            ->from(MaterialityQuestion::class, 'q')
            ->leftJoin(MaterialityQuestionTranslation::class, 'trans', 'WITH', 'trans.translatable = q AND trans.locale = :locale')
            ->setParameter('locale', LocaleGetter::locale())
        ;

        if (!$filter->includeRemoved) {
            $qb->andWhere($qb->expr()->eq('q.removed', $qb->expr()->literal(false)));
        }

        if (null !== $filter->text && '' !== $filter->text) {
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->andX(
                            $qb->expr()->like('q.text', ':text'),
                            $qb->expr()->isNull('trans.text'),
                        ),
                        $qb->expr()->like('trans.text', ':text'),
                    ),
                )
                ->setParameter('text', '%' . $filter->text . '%')
            ;
        }

        if (null !== $filter->criterion) {
            $qb
                ->andWhere($qb->expr()->eq('q.criterion', ':criterion'))
                ->setParameter('criterion', $filter->criterion->value)
            ;
        }

        if (null !== $filter->topicId) {
            $qb
                ->andWhere($qb->expr()->eq('q.topicId', ':topicId'))
                ->setParameter('topicId', (string) $filter->topicId)
            ;
        }

        if ($filter->onlyGeneric) {
            $qb->andWhere($qb->expr()->isNull('q.topicId'));
        }

        if (null !== $filter->active) {
            $qb
                ->andWhere($qb->expr()->eq('q.active', ':active'))
                ->setParameter('active', $filter->active)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'criterion' => $qb->orderBy('q.criterion', $way),
            'registeredAt' => $qb->orderBy('q.registeredAt', $way),
            default => $qb->orderBy('q.position', $way)->addOrderBy('q.registeredAt', $way),
        };

        $qb->addOrderBy('q.id', 'ASC');

        return $qb;
    }
}
