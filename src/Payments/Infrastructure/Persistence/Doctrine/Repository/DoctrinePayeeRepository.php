<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Persistence\Doctrine\Repository;

use App\Payments\Domain\Model\Payee\Payee;
use App\Payments\Domain\Model\Payee\PayeeFilter;
use App\Payments\Domain\Model\Payee\PayeeId;
use App\Payments\Domain\Model\Payee\PayeeRepository;
use App\Payments\Domain\Model\Payee\PayeeType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.payee', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrinePayeeRepository implements PayeeRepository
{
    public function __construct(private EntityManagerInterface $manager) {}

    public function add(Payee $payee): void
    {
        $this->manager->persist($payee);
        $this->manager->flush();
    }

    public function find(PayeeId $payeeId): ?Payee
    {
        $qb = $this->createQueryBuilderFromFilter(new PayeeFilter());

        return $qb
            ->andWhere($qb->expr()->eq('p.id', ':id'))
            ->setParameter('id', (string) $payeeId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findOneBySubject(PayeeType $type, string $subjectId): ?Payee
    {
        $qb = $this->createQueryBuilderFromFilter(new PayeeFilter(type: $type, subjectId: $subjectId));

        return $qb
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(PayeeFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('p')
            ->from(Payee::class, 'p')
        ;

        if (null !== $filter->type) {
            $qb
                ->andWhere($qb->expr()->eq('p.type', ':type'))
                ->setParameter('type', $filter->type->value)
            ;
        }

        if (null !== $filter->subjectId) {
            $qb
                ->andWhere($qb->expr()->eq('p.subjectId', ':subjectId'))
                ->setParameter('subjectId', $filter->subjectId)
            ;
        }

        if (null !== $filter->status) {
            $qb
                ->andWhere($qb->expr()->eq('p.status', ':status'))
                ->setParameter('status', $filter->status->value)
            ;
        }

        if (!empty($filter->search)) {
            $qb
                ->andWhere($qb->expr()->orX(
                    $qb->expr()->like('p.legalName', ':search'),
                    $qb->expr()->like('p.taxId', ':search'),
                ))
                ->setParameter('search', "%{$filter->search}%")
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'status' => $qb->orderBy('p.status', $way),
            'registeredAt' => $qb->orderBy('p.registeredAt', $way),
            default => $qb->orderBy('p.legalName', $way),
        };

        $qb->addOrderBy('p.id', 'ASC');

        return $qb;
    }

    /** @return array<Payee> */
    public function findByFilter(PayeeFilter $filter): array
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

    public function totalResultsByFilter(PayeeFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT p)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
