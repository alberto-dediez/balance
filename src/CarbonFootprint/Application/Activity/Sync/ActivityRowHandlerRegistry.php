<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Sync;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class ActivityRowHandlerRegistry
{
    /** @var array<string, ActivityRowHandler> */
    private array $handlers = [];

    /** @param iterable<ActivityRowHandler> $handlers */
    public function __construct(
        #[AutowireIterator('carbon_footprint.activity_row_handler')]
        iterable $handlers,
    ) {
        foreach ($handlers as $handler) {
            $this->handlers[$handler->activityType()->value] = $handler;
        }
    }

    public function forActivityType(ActivityType $activityType): ActivityRowHandler
    {
        return $this->handlers[$activityType->value]
            ?? throw new \InvalidArgumentException(sprintf('No activity row handler registered for activity type "%s".', $activityType->value));
    }
}
