<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\Integration;

interface IntegrationPositionRepository
{
    public function find(string $source, string $aggregateId): ?IntegrationPosition;

    public function save(IntegrationPosition $position): void;
}
