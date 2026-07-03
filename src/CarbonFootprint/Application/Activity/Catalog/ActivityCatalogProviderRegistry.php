<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Catalog;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class ActivityCatalogProviderRegistry
{
    /** @var array<string, ActivityCatalogProvider> */
    private array $providers = [];

    /** @param iterable<ActivityCatalogProvider> $providers */
    public function __construct(
        #[AutowireIterator('carbon_footprint.activity_catalog_provider')]
        iterable $providers,
    ) {
        foreach ($providers as $provider) {
            $this->providers[$provider->activityType()->value] = $provider;
        }
    }

    public function forActivityType(ActivityType $activityType): ActivityCatalogProvider
    {
        return $this->providers[$activityType->value]
            ?? throw new \InvalidArgumentException(sprintf('No activity catalog provider registered for activity type "%s".', $activityType->value));
    }
}
