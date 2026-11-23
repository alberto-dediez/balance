<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\Integration;

interface IntegrationOutboxRepository
{
    public function add(IntegrationOutboxMessage $message): void;

    public function save(IntegrationOutboxMessage $message): void;

    /** @return list<IntegrationOutboxMessage> */
    public function pending(int $limit): array;

    public function oldestPendingOccurredOn(): ?\DateTimeImmutable;

    /** @return list<IntegrationOutboxMessage> */
    public function list(IntegrationOutboxFilter $filter): array;

    public function purgeSentBefore(\DateTimeImmutable $limit): int;
}
