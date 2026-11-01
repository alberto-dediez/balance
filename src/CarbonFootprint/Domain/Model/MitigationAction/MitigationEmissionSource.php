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

    public static function compare(self $a, self $b): int
    {
        $scopes = array_flip(array_map(static fn(Scope $scope): string => $scope->value, Scope::cases()));
        $sources = array_flip(array_map(static fn(Source $source): string => $source->value, Source::cases()));

        return [$scopes[$a->scope->value], $sources[$a->source->value], $a->itemName]
            <=> [$scopes[$b->scope->value], $sources[$b->source->value], $b->itemName];
    }
}
