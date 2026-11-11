<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Persistence\Doctrine\Repository;

use App\Security\Domain\Model\ApiToken\ApiToken;
use App\Security\Domain\Model\ApiToken\ApiTokenFilter;
use App\Security\Domain\Model\ApiToken\ApiTokenId;
use App\Security\Domain\Model\ApiToken\ApiTokenRepository;
use App\Security\Domain\Model\ApiToken\ApiTokenStatus;
use App\Security\Domain\Model\User\UserId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\When;

#[AsAlias(id: 'repository.api_token', public: true)]
#[When(env: 'dev')]
#[When(env: 'prod')]
class DoctrineApiTokenRepository implements ApiTokenRepository
{
    public function __construct(private readonly EntityManagerInterface $manager) {}

    public function add(ApiToken $apiToken): void
    {
        $this->manager->persist($apiToken);
        $this->manager->flush();
    }

    public function find(ApiTokenId $apiTokenId): ?ApiToken
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb->select('at')
            ->from(ApiToken::class, 'at')
            ->where($qb->expr()->eq('at.id', ':id'))
            ->setParameter('id', (string) $apiTokenId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByTokenHash(string $tokenHash): ?ApiToken
    {
        $qb = $this->manager->createQueryBuilder();

        return $qb->select('at')
            ->from(ApiToken::class, 'at')
            ->where($qb->expr()->eq('at.tokenHash', ':hash'))
            ->setParameter('hash', $tokenHash)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /** @return array<ApiToken> */
    public function findByFilter(ApiTokenFilter $filter, \DateTimeImmutable $now): array
    {
        $qb = $this->createQueryBuilderFromFilter($filter, $now);

        $query = $qb->getQuery();

        if ($filter->limit > 0) {
            $query
                ->setMaxResults($filter->limit)
                ->setFirstResult($filter->page * $filter->limit)
            ;
        }

        return (array) $query->getResult();
    }

    public function totalResultsByFilter(ApiTokenFilter $filter, \DateTimeImmutable $now): int
    {
        $qb = $this->createQueryBuilderFromFilter($filter, $now);

        return (int) $qb->select('COUNT(DISTINCT at)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function countActiveByUserId(UserId $userId, \DateTimeImmutable $now): int
    {
        return $this->totalResultsByFilter(new ApiTokenFilter(userId: $userId, status: ApiTokenStatus::Active), $now);
    }

    /** @return array<ApiToken> */
    public function findActiveByUserId(UserId $userId, \DateTimeImmutable $now): array
    {
        return $this->findByFilter(new ApiTokenFilter(userId: $userId, status: ApiTokenStatus::Active, limit: 0), $now);
    }

    private function createQueryBuilderFromFilter(ApiTokenFilter $filter, \DateTimeImmutable $now): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();

        $qb->select('at')
            ->from(ApiToken::class, 'at')
        ;

        if (null !== $filter->userId) {
            $qb
                ->andWhere($qb->expr()->eq('at.userId', ':userId'))
                ->setParameter('userId', (string) $filter->userId)
            ;
        }

        match ($filter->status) {
            ApiTokenStatus::Revoked => $qb->andWhere($qb->expr()->isNotNull('at.revokedAt')),
            ApiTokenStatus::Expired => $qb
                ->andWhere($qb->expr()->isNull('at.revokedAt'))
                ->andWhere($qb->expr()->isNotNull('at.expiresAt'))
                ->andWhere($qb->expr()->lte('at.expiresAt', ':now'))
                ->setParameter('now', $now),
            ApiTokenStatus::Active => $qb
                ->andWhere($qb->expr()->isNull('at.revokedAt'))
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->isNull('at.expiresAt'),
                        $qb->expr()->gt('at.expiresAt', ':now'),
                    ),
                )
                ->setParameter('now', $now),
            null => $qb,
        };

        $way = 'ASC' === $filter->orderWay ? 'ASC' : 'DESC';

        match ($filter->orderBy) {
            'name' => $qb->orderBy('at.name', $way),
            'expiresAt' => $qb->orderBy('at.expiresAt', $way),
            'lastUsedAt' => $qb->orderBy('at.lastUsedAt', $way),
            default => $qb->orderBy('at.registeredAt', 'DESC'),
        };

        return $qb;
    }
}
