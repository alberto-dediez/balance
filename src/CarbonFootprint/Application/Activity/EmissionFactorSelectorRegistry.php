<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class EmissionFactorSelectorRegistry
{
    /** @var array<string, EmissionFactorSelector> */
    private array $selectors = [];

    /** @param iterable<EmissionFactorSelector> $selectors */
    public function __construct(
        #[AutowireIterator('carbon_footprint.emission_factor_selector')]
        iterable $selectors,
    ) {
        foreach ($selectors as $selector) {
            $this->selectors[$selector->activityType()->value] = $selector;
        }
    }

    public function forActivityType(ActivityType $activityType): EmissionFactorSelector
    {
        return $this->selectors[$activityType->value]
            ?? throw new \InvalidArgumentException(sprintf('No emission factor selector registered for activity type "%s".', $activityType->value));
    }
}
