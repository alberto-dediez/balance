<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\ApiToken;

use App\Security\Domain\Model\User\UserId;

interface ApiTokenRepository
{
    public function add(ApiToken $apiToken): void;

    public function find(ApiTokenId $apiTokenId): ?ApiToken;

    public function findByTokenHash(string $tokenHash): ?ApiToken;

    /** @return array<ApiToken> */
    public function findByFilter(ApiTokenFilter $filter, \DateTimeImmutable $now): array;

    public function totalResultsByFilter(ApiTokenFilter $filter, \DateTimeImmutable $now): int;

    public function countActiveByUserId(UserId $userId, \DateTimeImmutable $now): int;

    /** @return array<ApiToken> */
    public function findActiveByUserId(UserId $userId, \DateTimeImmutable $now): array;
}
