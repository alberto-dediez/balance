<?php

namespace App\Waste\Infrastructure\Repository\Waste;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Waste\Domain\Model\Waste\Waste;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\Waste\WasteFilter;
use App\Waste\Domain\Model\Waste\WasteRepository;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(id: 'repository.waste', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineWasteRepository implements WasteRepository
{
    private EntityRepository $repository;

    public function __construct(private EntityManagerInterface $manager)
    {
        $this->repository = $manager->getRepository(Waste::class);
    }

    public function add(Waste $waste): void
    {
        $this->getEntityManager()->persist($waste);
        $this->getEntityManager()->flush();
    }

    public function remove(Waste $waste): void
    {
        $this->getEntityManager()->remove($waste);
        $this->getEntityManager()->flush();
    }

    public function find(WasteId $wasteId): ?Waste
    {
        $qb = $this->createQueryBuilderFromFilter(new WasteFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('w.id.id', ':id')
            )
            ->setParameter('id', (string) $wasteId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByName(string $name): ?Waste
    {
        $qb = $this->createQueryBuilderFromFilter(new WasteFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('w.name', ':name')
            )
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(WasteFilter $filter): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('w')
            ->from(Waste::class, 'w')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('w.removed'),
                    $qb->expr()->eq('w.removed', $qb->expr()->literal(false))
                )
            )
        ;

        if (!empty($filter->name)) {
            $qb
                ->andWhere(
                    $qb->expr()->like('w.name', ':name')
                )
                ->setParameter('name', "%{$filter->name}%")
            ;
        }

        if (null !== $filter->isDangerous) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('w.isDangerous', ':isDangerous')
                )
                ->setParameter('isDangerous', $filter->isDangerous)
            ;
        }

        $way = $filter->orderWay == 'ASC' ? 'ASC' : 'DESC';

        switch ($filter->orderBy) {
            case 'registeredAt':
                $qb->orderBy('w.registeredAt', $way);
                break;
            case 'name':
            default:
                $qb->orderBy('w.name', $way);        
                break;
        }

        return $qb;
    }

    public function findByFilter(WasteFilter $filter): array
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (array) $qb
            ->getQuery()
            ->setMaxResults($filter->limit)
            ->setFirstResult($filter->page * $filter->limit)
            ->getResult()
        ;
    }

    public function totalResultsByFilter(WasteFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return $qb->select('COUNT(DISTINCT w)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function getEntityManager()
    {
        return $this->manager;
    }
}