<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Persistence\InMemory\Repository;

use App\Security\Domain\Model\ApiToken\ApiToken;
use App\Security\Domain\Model\ApiToken\ApiTokenFilter;
use App\Security\Domain\Model\ApiToken\ApiTokenId;
use App\Security\Domain\Model\ApiToken\ApiTokenRepository;
use App\Security\Domain\Model\ApiToken\ApiTokenStatus;
use App\Security\Domain\Model\User\UserId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryApiTokenRepository implements ApiTokenRepository
{
    /** @param array<string, ApiToken> $apiTokens */
    public function __construct(
        private array $apiTokens = [],
    ) {}

    public function add(ApiToken $apiToken): void
    {
        $this->apiTokens[(string) $apiToken->id()] = $apiToken;
    }

    public function find(ApiTokenId $apiTokenId): ?ApiToken
    {
        return $this->apiTokens[(string) $apiTokenId] ?? null;
    }

    public function findByTokenHash(string $tokenHash): ?ApiToken
    {
        foreach ($this->apiTokens as $apiToken) {
            if ($apiToken->tokenHash() === $tokenHash) {
                return $apiToken;
            }
        }

        return null;
    }

    /** @return array<int, ApiToken> */
    public function findByFilter(ApiTokenFilter $filter, \DateTimeImmutable $now): array
    {
        $apiTokens = array_values(array_filter(
            $this->apiTokens,
            static fn(ApiToken $apiToken): bool => (null === $filter->userId || $apiToken->belongsTo($filter->userId))
                && (null === $filter->status || $filter->status === $apiToken->status($now)),
        ));

        $way = 'ASC' === $filter->orderWay ? 1 : -1;

        usort($apiTokens, static fn(ApiToken $a, ApiToken $b): int => match ($filter->orderBy) {
            'name' => $way * strcmp($a->name(), $b->name()),
            'expiresAt' => $way * ($a->expiresAt() <=> $b->expiresAt()),
            'lastUsedAt' => $way * ($a->lastUsedAt() <=> $b->lastUsedAt()),
            default => $b->registeredAt() <=> $a->registeredAt(),
        });

        if ($filter->limit > 0) {
            return array_slice($apiTokens, $filter->page * $filter->limit, $filter->limit);
        }

        return $apiTokens;
    }

    public function totalResultsByFilter(ApiTokenFilter $filter, \DateTimeImmutable $now): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 0;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter, $now));
    }

    public function countActiveByUserId(UserId $userId, \DateTimeImmutable $now): int
    {
        return count($this->findActiveByUserId($userId, $now));
    }

    /** @return array<int, ApiToken> */
    public function findActiveByUserId(UserId $userId, \DateTimeImmutable $now): array
    {
        return $this->findByFilter(new ApiTokenFilter(userId: $userId, status: ApiTokenStatus::Active, limit: 0), $now);
    }
}
