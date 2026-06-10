<?php

declare(strict_types=1);

namespace App\DataExchange\Infrastructure\Persistence\InMemory\Repository;

use App\DataExchange\Domain\Model\Exportation\Exportation;
use App\DataExchange\Domain\Model\Exportation\ExportationId;
use App\DataExchange\Domain\Model\Exportation\ExportationRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryExportationRepository implements ExportationRepository
{
    /** @param array<string, Exportation> $exportations */
    public function __construct(private array $exportations = []) {}

    public function add(Exportation $exportation): void
    {
        $this->exportations[(string) $exportation->id()] = $exportation;
    }

    public function remove(Exportation $exportation): void
    {
        unset($this->exportations[(string) $exportation->id()]);
    }

    public function find(ExportationId $exportationId): ?Exportation
    {
        return $this->exportations[(string) $exportationId] ?? null;
    }

    /** @return Exportation[] */
    public function findExpired(\DateTimeImmutable $now): array
    {
        return array_values(
            array_filter(
                $this->exportations,
                static fn(Exportation $exportation): bool => $exportation->isExpired($now),
            ),
        );
    }
}
