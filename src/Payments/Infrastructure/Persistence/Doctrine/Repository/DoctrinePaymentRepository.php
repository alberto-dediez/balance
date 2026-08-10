<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Persistence\Doctrine\Repository;

use App\Payments\Domain\Model\Payment\Payment;
use App\Payments\Domain\Model\Payment\PaymentFilter;
use App\Payments\Domain\Model\Payment\PaymentId;
use App\Payments\Domain\Model\Payment\PaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.payment', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrinePaymentRepository implements PaymentRepository
{
    public function __construct(private EntityManagerInterface $manager) {}

    public function add(Payment $payment): void
    {
        $this->manager->persist($payment);
        $this->manager->flush();
    }

    public function find(PaymentId $paymentId): ?Payment
    {
        $qb = $this->createQueryBuilderFromFilter(new PaymentFilter());

        return $qb
            ->andWhere($qb->expr()->eq('p.id', ':id'))
            ->setParameter('id', (string) $paymentId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findOneByExternalId(string $externalId): ?Payment
    {
        $qb = $this->createQueryBuilderFromFilter(new PaymentFilter());

        return $qb
            ->andWhere($qb->expr()->eq('p.externalId', ':externalId'))
            ->setParameter('externalId', $externalId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(PaymentFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('p')
            ->from(Payment::class, 'p')
        ;

        if (null !== $filter->subjectType) {
            $qb
                ->andWhere($qb->expr()->eq('p.subjectType', ':subjectType'))
                ->setParameter('subjectType', $filter->subjectType)
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

        if (null !== $filter->externalId) {
            $qb
                ->andWhere($qb->expr()->eq('p.externalId', ':externalId'))
                ->setParameter('externalId', $filter->externalId)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'startedAt' => $qb->orderBy('p.startedAt', $way),
            'resolvedAt' => $qb->orderBy('p.resolvedAt', $way),
            default => $qb->orderBy('p.startedAt', $way),
        };

        $qb->addOrderBy('p.id', 'ASC');

        return $qb;
    }

    /** @return array<Payment> */
    public function findByFilter(PaymentFilter $filter): array
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

    public function totalResultsByFilter(PaymentFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT p)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
