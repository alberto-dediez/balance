<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\Integration;

interface IntegrationInboxRepository
{
    public function has(string $messageId): bool;

    public function find(string $messageId): ?IntegrationInboxMessage;

    public function add(IntegrationInboxMessage $message): void;

    /** @return list<IntegrationInboxMessage> */
    public function list(IntegrationInboxFilter $filter): array;

    public function purgeReceivedBefore(\DateTimeImmutable $limit): int;
}
