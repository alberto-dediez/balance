<?php

declare(strict_types=1);

namespace App\DataExchange\Domain\Model\Importation;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class ImportationTypeFactory
{
    private array $types = [];

    public function __construct(
        #[AutowireIterator('exchange.importation.type')]
        iterable $importationTypes,
    ) {
        foreach ($importationTypes as $importationType) {
            $this->types[$importationType->code()] = $importationType;
        }
    }

    public function createFromCode(string $code): ?ImportationType
    {
        return $this->types[$code] ?? null;
    }
}
