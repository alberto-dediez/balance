<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Persistence\Doctrine\Repository;

use App\Payments\Domain\Model\Payee\PayeeId;
use App\Payments\Domain\Model\Payout\Payout;
use App\Payments\Domain\Model\Payout\PayoutFilter;
use App\Payments\Domain\Model\Payout\PayoutId;
use App\Payments\Domain\Model\Payout\PayoutRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.payout', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrinePayoutRepository implements PayoutRepository
{
    public function __construct(private EntityManagerInterface $manager) {}

    public function add(Payout $payout): void
    {
        $this->manager->persist($payout);
        $this->manager->flush();
    }

    public function find(PayoutId $payoutId): ?Payout
    {
        $qb = $this->createQueryBuilderFromFilter(new PayoutFilter());

        return $qb
            ->andWhere($qb->expr()->eq('p.id', ':id'))
            ->setParameter('id', (string) $payoutId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findOneByNumber(string $number): ?Payout
    {
        $qb = $this->createQueryBuilderFromFilter(new PayoutFilter());

        return $qb
            ->andWhere($qb->expr()->eq('p.number', ':number'))
            ->setParameter('number', $number)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(PayoutFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('p')
            ->from(Payout::class, 'p')
        ;

        if (null !== $filter->payeeId) {
            $qb
                ->andWhere($qb->expr()->eq('p.payeeId', ':payeeId'))
                ->setParameter('payeeId', (string) $filter->payeeId)
            ;
        }

        if (null !== $filter->payeeIds) {
            $qb
                ->andWhere($qb->expr()->in('p.payeeId', ':payeeIds'))
                ->setParameter('payeeIds', array_map(static fn(PayeeId $payeeId): string => (string) $payeeId, $filter->payeeIds))
            ;
        }

        if (null !== $filter->status) {
            $qb
                ->andWhere($qb->expr()->eq('p.status', ':status'))
                ->setParameter('status', $filter->status->value)
            ;
        }

        if (!empty($filter->number)) {
            $qb
                ->andWhere($qb->expr()->like('p.number', ':number'))
                ->setParameter('number', "%{$filter->number}%")
            ;
        }

        if (!empty($filter->reference)) {
            $qb
                ->andWhere($qb->expr()->like('p.reference', ':reference'))
                ->setParameter('reference', "%{$filter->reference}%")
            ;
        }

        if ($filter->from instanceof \DateTimeImmutable) {
            $qb
                ->andWhere($qb->expr()->gte('p.createdAt', ':from'))
                ->setParameter('from', $filter->from)
            ;
        }

        if ($filter->to instanceof \DateTimeImmutable) {
            $qb
                ->andWhere($qb->expr()->lte('p.createdAt', ':to'))
                ->setParameter('to', $filter->to)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'number' => $qb->orderBy('p.number', $way),
            'baseAmount' => $qb->orderBy('p.baseAmount', $way),
            default => $qb->orderBy('p.createdAt', $way),
        };

        $qb->addOrderBy('p.id', 'ASC');

        return $qb;
    }

    /** @return array<Payout> */
    public function findByFilter(PayoutFilter $filter): array
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

    public function totalResultsByFilter(PayoutFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT p)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /** @return list<string> */
    public function numbersOfYear(int $year): array
    {
        $qb = $this->manager->createQueryBuilder();

        /** @var list<array{number: string}> $rows */
        $rows = $qb
            ->select('p.number as number')
            ->from(Payout::class, 'p')
            ->andWhere($qb->expr()->like('p.number', ':prefix'))
            ->setParameter('prefix', sprintf('%d/%%', $year))
            ->orderBy('p.number', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return array_map(static fn(array $row): string => $row['number'], $rows);
    }
}
