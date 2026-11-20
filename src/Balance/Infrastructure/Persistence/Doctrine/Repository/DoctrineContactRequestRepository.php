<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Persistence\Doctrine\Repository;

use App\Balance\Domain\Model\ContactRequest\ContactRequest;
use App\Balance\Domain\Model\ContactRequest\ContactRequestAlreadyExistsException;
use App\Balance\Domain\Model\ContactRequest\ContactRequestFilter;
use App\Balance\Domain\Model\ContactRequest\ContactRequestId;
use App\Balance\Domain\Model\ContactRequest\ContactRequestRepository;
use App\Balance\Domain\Model\ContactRequest\ContactRequestStatus;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.contact_request', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineContactRequestRepository implements ContactRequestRepository
{
    public function __construct(private EntityManagerInterface $manager) {}

    public function add(ContactRequest $contactRequest): void
    {
        $this->manager->persist($contactRequest);

        try {
            $this->manager->flush();
        } catch (UniqueConstraintViolationException) {
            throw new ContactRequestAlreadyExistsException($contactRequest->origin(), $contactRequest->originId());
        }
    }

    public function find(ContactRequestId $contactRequestId): ?ContactRequest
    {
        $qb = $this->createQueryBuilderFromFilter(new ContactRequestFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('cr.id', ':id'),
            )
            ->setParameter('id', (string) $contactRequestId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findOneByOrigin(ProjectContributionOrigin $origin, string $originId): ?ContactRequest
    {
        $qb = $this->createQueryBuilderFromFilter(new ContactRequestFilter());

        return $qb
            ->andWhere($qb->expr()->eq('cr.origin', ':origin'))
            ->andWhere($qb->expr()->eq('cr.originId', ':originId'))
            ->setParameter('origin', $origin->value)
            ->setParameter('originId', $originId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(ContactRequestFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('cr')
            ->from(ContactRequest::class, 'cr')
        ;

        if (null !== $filter->resolved) {
            $qb
                ->andWhere(
                    $filter->resolved
                        ? $qb->expr()->neq('cr.status', ':pendingStatus')
                        : $qb->expr()->eq('cr.status', ':pendingStatus'),
                )
                ->setParameter('pendingStatus', ContactRequestStatus::Pending->value)
            ;
        }

        if (null !== $filter->accountId) {
            $qb
                ->andWhere($qb->expr()->eq('cr.accountId', ':accountId'))
                ->setParameter('accountId', (string) $filter->accountId)
            ;
        }

        if (!in_array($filter->search, [null, ''], true)) {
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->like('cr.contactName', ':search'),
                        $qb->expr()->like('cr.contactEmail', ':search'),
                    ),
                )
                ->setParameter('search', "%{$filter->search}%")
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'requestedAt' => $qb->orderBy('cr.requestedAt', $way),
            default => $qb->orderBy('cr.requestedAt', 'DESC'),
        };

        $qb->addOrderBy('cr.id', 'ASC');

        return $qb;
    }

    /** @return array<ContactRequest> */
    public function findByFilter(ContactRequestFilter $filter): array
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

    public function totalResultsByFilter(ContactRequestFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT cr)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
