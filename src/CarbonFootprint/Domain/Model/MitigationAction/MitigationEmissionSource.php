<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MitigationAction;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;

final class MitigationEmissionSource
{
    public function __construct(
        public readonly Scope $scope,
        public readonly Source $source,
        public readonly string $itemId,
        public readonly string $itemName,
        public readonly float $emissions,
        public readonly ?string $originKey = null,
    ) {}

    public function key(): string
    {
        return $this->source->value . '|' . $this->itemId;
    }

    public function isDerived(): bool
    {
        return null !== $this->originKey;
    }

    public function reductionKey(): string
    {
        return $this->originKey ?? $this->key();
    }
}
