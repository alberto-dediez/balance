<?php

declare(strict_types=1);

namespace App\DataExchange\Domain\Model\Exportation;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class ExportationTypeFactory
{
    /** @var array<string, ExportationType> */
    private array $types = [];

    /** @param iterable<ExportationType> $exportationTypes */
    public function __construct(
        #[AutowireIterator('exchange.exportation.type')]
        iterable $exportationTypes,
    ) {
        foreach ($exportationTypes as $exportationType) {
            $this->types[$exportationType->code()] = $exportationType;
        }
    }

    public function createFromCode(string $code): ?ExportationType
    {
        return $this->types[$code] ?? null;
    }
}
