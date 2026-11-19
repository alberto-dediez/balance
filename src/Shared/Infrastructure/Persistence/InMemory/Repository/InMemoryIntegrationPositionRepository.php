<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\InMemory\Repository;

use App\Shared\Domain\Model\Integration\IntegrationPosition;
use App\Shared\Domain\Model\Integration\IntegrationPositionRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryIntegrationPositionRepository implements IntegrationPositionRepository
{
    /** @var array<string, IntegrationPosition> */
    private array $positions = [];

    public function find(string $source, string $aggregateId): ?IntegrationPosition
    {
        return $this->positions[$this->key($source, $aggregateId)] ?? null;
    }

    public function save(IntegrationPosition $position): void
    {
        $this->positions[$this->key($position->source(), $position->aggregateId())] = $position;
    }

    private function key(string $source, string $aggregateId): string
    {
        return $source . '|' . $aggregateId;
    }
}
