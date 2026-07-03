<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class ActivityRowViewFactoryRegistry
{
    /** @var array<string, ActivityRowViewFactory> */
    private array $factories = [];

    /** @param iterable<ActivityRowViewFactory> $factories */
    public function __construct(
        #[AutowireIterator('carbon_footprint.activity_row_view_factory')]
        iterable $factories,
    ) {
        foreach ($factories as $factory) {
            $this->factories[$factory->activityType()->value] = $factory;
        }
    }

    public function forActivityType(ActivityType $activityType): ActivityRowViewFactory
    {
        return $this->factories[$activityType->value]
            ?? throw new \InvalidArgumentException(sprintf('No activity row view factory registered for activity type "%s".', $activityType->value));
    }
}
