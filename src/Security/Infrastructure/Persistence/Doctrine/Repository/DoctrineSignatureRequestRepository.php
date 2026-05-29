<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Persistence\Doctrine\Repository;

use App\Security\Domain\Model\SignatureRequest\SignatureRequest;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestFilter;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.signature_request', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineSignatureRequestRepository implements SignatureRequestRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(SignatureRequest $signatureRequest): void
    {
        $this->manager->persist($signatureRequest);
        $this->manager->flush();
    }

    public function remove(SignatureRequest $signatureRequest): void
    {
        $this->manager->remove($signatureRequest);
        $this->manager->flush();
    }

    public function find(SignatureRequestId $signatureRequestId): ?SignatureRequest
    {
        $qb = $this->createQueryBuilderFromFilter(new SignatureRequestFilter());

        return $qb
            ->andWhere(
                $qb->expr()->eq('sr.id', ':id'),
            )
            ->setParameter('id', (string) $signatureRequestId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByToken(string $tokenHash): ?SignatureRequest
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb->select('sr')
            ->from(SignatureRequest::class, 'sr')
            ->where($qb->expr()->eq('sr.token.hash', ':hash'))
            ->setParameter('hash', $tokenHash)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function createQueryBuilderFromFilter(SignatureRequestFilter $filter): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('sr')
            ->from(SignatureRequest::class, 'sr')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNull('sr.removed'),
                    $qb->expr()->eq('sr.removed', ':removed'),
                ),
            )
            ->setParameter('removed', false, Types::BOOLEAN)
        ;

        if (null !== $filter->accountId) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('sr.accountId', ':accountId'),
                )
                ->setParameter('accountId', (string) $filter->accountId)
            ;
        }

        if (null !== $filter->status) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('sr.status', ':status'),
                )
                ->setParameter('status', $filter->status->value)
            ;
        }

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'expiresAt' => $qb->orderBy('sr.expiresAt', $way),
            'status' => $qb->orderBy('sr.status', $way),
            default => $qb->orderBy('sr.registeredAt', $way),
        };

        return $qb;
    }

    /** @return array<SignatureRequest> */
    public function findByFilter(SignatureRequestFilter $filter): array
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        $query = $qb->getQuery();

        if ($filter->limit > 0) {
            $query
                ->setMaxResults($filter->limit)
                ->setFirstResult($filter->page * $filter->limit)
            ;
        }

        return (array) $query->getResult();
    }

    public function totalResultsByFilter(SignatureRequestFilter $filter): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter);

        return (int) $qb->select('COUNT(DISTINCT sr)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
