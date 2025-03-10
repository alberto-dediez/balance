<?php

namespace App\Waste\Infrastructure\Repository\Property;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Waste\Domain\Model\Property\Property;
use App\Waste\Domain\Model\Property\PropertyId;
use App\Waste\Domain\Model\Property\PropertyFilter;
use App\Waste\Domain\Model\Property\PropertyRepository;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use App\Security\Infrastructure\Repository\Account\DoctrineAccountRepository;

#[AsAlias(id: 'repository.property', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrinePropertyRepository implements PropertyRepository
{
    private EntityRepository $repository;

    public function __construct(private EntityManagerInterface $manager, private DoctrineAccountRepository $accountRepository)
    {
        $this->repository = $manager->getRepository(Property::class);
    }

    public function add(Property $property): void
    {
        if (null == $property->account() && null !== $property->accountId()) {
            $account = $this->accountRepository->find($property->accountId());

            $property = new Property(
                id: $property->id(),
                name: $property->name(),
                accountId: $property->accountId(),
                account: $account,
                events: $property->recordedEvents(),
                registeredAt: $property->registeredAt(),
                removed: $property->isRemoved(),
                removedAt: $property->removedAt()
            );
        }

        $this->getEntityManager()->persist($property);
        $this->getEntityManager()->flush();
    }

    public function remove(Property $property): void
    {
        $this->getEntityManager()->remove($property);
        $this->getEntityManager()->flush();
    }

    public function find(PropertyId $propertyId): ?Property
    {
        $qb = $this->createQueryBuilderFromFilter(new PropertyFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('p.id.id', ':id')
            )
            ->setParameter('id', (string) $propertyId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByName(string $name): ?Property
    {
        $qb = $this->createQueryBuilderFromFilter(new PropertyFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('p.name', ':name')
            )
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(PropertyFilter $filter): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('p, a')
            ->from(Property::class, 'p')
            ->leftJoin('p.account', 'a')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('p.removed'),
                    $qb->expr()->eq('p.removed', $qb->expr()->literal(false))
                )
            )
        ;

        if (!empty($filter->name)) {
            $qb
                ->andWhere(
                    $qb->expr()->like('p.name', ':name')
                )
                ->setParameter('name', "%{$filter->name}%")
            ;
        }

        if (null !== $filter->account) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('p.accountId', ':accountId')
                )
                ->setParameter('accountId', (string) $filter->account->id())
            ;
        }

        $way = $filter->orderWay == 'ASC' ? 'ASC' : 'DESC';

        switch ($filter->orderBy) {
            case 'registeredAt':
                $qb->orderBy('p.registeredAt', $way);
                break;
            case 'name':
            default:
                $qb->orderBy('p.name', $way);        
                break;
        }

        return $qb;
    }

    public function findByFilter(PropertyFilter $filter): array
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (array) $qb
            ->getQuery()
            ->setMaxResults($filter->limit)
            ->setFirstResult($filter->page * $filter->limit)
            ->getResult()
        ;
    }

    public function totalResultsByFilter(PropertyFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return $qb->select('COUNT(DISTINCT p)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function getEntityManager()
    {
        return $this->manager;
    }
}