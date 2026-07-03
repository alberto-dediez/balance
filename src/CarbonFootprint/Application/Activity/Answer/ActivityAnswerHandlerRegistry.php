<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Answer;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class ActivityAnswerHandlerRegistry
{
    /** @var array<string, ActivityAnswerHandler> */
    private array $handlers = [];

    /** @param iterable<ActivityAnswerHandler> $handlers */
    public function __construct(
        #[AutowireIterator('carbon_footprint.activity_answer_handler')]
        iterable $handlers,
    ) {
        foreach ($handlers as $handler) {
            $this->handlers[$handler->activityType()->value] = $handler;
        }
    }

    public function forActivityType(ActivityType $activityType): ActivityAnswerHandler
    {
        return $this->handlers[$activityType->value]
            ?? throw new \InvalidArgumentException(sprintf('No activity answer handler registered for activity type "%s".', $activityType->value));
    }
}
