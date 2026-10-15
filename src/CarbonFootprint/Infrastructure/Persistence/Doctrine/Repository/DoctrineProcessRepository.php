<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\Process\Process;
use App\CarbonFootprint\Domain\Model\Process\ProcessFilter;
use App\CarbonFootprint\Domain\Model\Process\ProcessId;
use App\CarbonFootprint\Domain\Model\Process\ProcessRepository;
use App\CarbonFootprint\Domain\Model\Process\ProcessTranslation;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Infrastructure\Persistence\Doctrine\Repository\DoctrineTranslatableRepositoryTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.process', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineProcessRepository implements ProcessRepository
{
    use DoctrineTranslatableRepositoryTrait;

    public function __construct(private EntityManagerInterface $manager) {}

    public function add(Process $process): void
    {
        $this->manager->persist($process);
        $this->addTranslations($process, ProcessTranslation::class, $this->manager);
        $this->manager->flush();
    }

    public function remove(Process $process): void
    {
        $this->manager->remove($process);
        $this->manager->flush();
    }

    public function find(ProcessId $processId): ?Process
    {
        $qb = $this->createQueryBuilderFromFilter(new ProcessFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('pr.id', ':id'),
            )
            ->setParameter('id', (string) $processId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(ProcessFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('pr')
            ->from(Process::class, 'pr')
            ->leftJoin(ProcessTranslation::class, 'trans', 'WITH', 'trans.translatable = pr AND trans.locale = :locale')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('pr.removed'),
                    $qb->expr()->eq('pr.removed', $qb->expr()->literal(false)),
                ),
            )
            ->setParameter('locale', LocaleGetter::locale())
        ;

        if (!empty($filter->name)) {
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->andX(
                            $qb->expr()->like('pr.name', ':name'),
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
            'registeredAt' => $qb->orderBy('pr.registeredAt', $way),
            'updatedAt' => $qb->orderBy('pr.updatedAt', $way),
            default => $qb->orderBy('pr.name', $way),
        };

        $qb->addOrderBy('pr.id', 'ASC');

        return $qb;
    }

    /** @return array<Process> */
    public function findByFilter(ProcessFilter $filter): array
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

    public function totalResultsByFilter(ProcessFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT pr)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->manager;
    }
}
