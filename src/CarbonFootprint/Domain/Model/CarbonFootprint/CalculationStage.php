<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprint;

interface CalculationStage
{
    public function key(): string;

    /** @return list<SectionDefinition> */
    public function sections(): array;
}
