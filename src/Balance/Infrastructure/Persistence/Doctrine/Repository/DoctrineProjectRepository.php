<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Persistence\Doctrine\Repository;

use App\Balance\Domain\Model\Project\Project;
use App\Balance\Domain\Model\Project\ProjectFilter;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Domain\Model\Project\ProjectTranslation;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Infrastructure\Persistence\Doctrine\Repository\DoctrineTranslatableRepositoryTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.project', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineProjectRepository implements ProjectRepository
{
    use DoctrineTranslatableRepositoryTrait;

    public function __construct(private EntityManagerInterface $manager) {}

    public function add(Project $project): void
    {
        $this->manager->persist($project);
        $this->addTranslations($project, ProjectTranslation::class, $this->manager);
        $this->manager->flush();
    }

    public function remove(Project $project): void
    {
        $this->manager->remove($project);
        $this->manager->flush();
    }

    public function find(ProjectId $projectId): ?Project
    {
        $qb = $this->createQueryBuilderFromFilter(new ProjectFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('p.id', ':id'),
            )
            ->setParameter('id', (string) $projectId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(ProjectFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('p')
            ->from(Project::class, 'p')
            ->leftJoin(ProjectTranslation::class, 'trans', 'WITH', 'trans.translatable = p AND trans.locale = :locale')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('p.removed'),
                    $qb->expr()->eq('p.removed', $qb->expr()->literal(false)),
                ),
            )
            ->setParameter('locale', LocaleGetter::locale())
        ;

        if (!empty($filter->name)) {
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->andX(
                            $qb->expr()->like('p.name', ':name'),
                            $qb->expr()->isNull('trans.name'),
                        ),
                        $qb->expr()->like('trans.name', ':name'),
                    ),
                )
                ->setParameter('name', "%{$filter->name}%")
            ;
        }

        if (null !== $filter->location) {
            $qb
                ->andWhere($qb->expr()->eq('p.location', ':location'))
                ->setParameter('location', (string) $filter->location)
            ;
        }

        if (null !== $filter->projectType) {
            $qb
                ->andWhere($qb->expr()->eq('p.projectType', ':projectType'))
                ->setParameter('projectType', $filter->projectType->value)
            ;
        }

        if (null !== $filter->projectStatus) {
            $qb
                ->andWhere($qb->expr()->eq('p.projectStatus', ':projectStatus'))
                ->setParameter('projectStatus', $filter->projectStatus->value)
            ;
        }

        if (!empty($filter->mitecoId)) {
            $qb
                ->andWhere($qb->expr()->like('p.mitecoId', ':mitecoId'))
                ->setParameter('mitecoId', "%{$filter->mitecoId}%")
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'registeredAt' => $qb->orderBy('p.registeredAt', $way),
            'updatedAt' => $qb->orderBy('p.updatedAt', $way),
            default => $qb->orderBy('p.name', $way),
        };

        $qb->addOrderBy('p.id', 'ASC');

        return $qb;
    }

    /** @return array<Project> */
    public function findByFilter(ProjectFilter $filter): array
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

    public function totalResultsByFilter(ProjectFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT p)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->manager;
    }
}
