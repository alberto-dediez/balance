<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class SectionAnswerHttpMapperRegistry
{
    /** @var array<string, SectionAnswerHttpMapper> */
    private array $mappers = [];

    /** @param iterable<SectionAnswerHttpMapper> $mappers */
    public function __construct(
        #[AutowireIterator('carbon_footprint.section_answer_http_mapper')]
        iterable $mappers,
    ) {
        foreach ($mappers as $mapper) {
            $this->mappers[$mapper->activityType()->value] = $mapper;
        }
    }

    public function forActivityType(ActivityType $activityType): SectionAnswerHttpMapper
    {
        return $this->mappers[$activityType->value]
            ?? throw new \InvalidArgumentException(sprintf('No section answer http mapper registered for activity type "%s".', $activityType->value));
    }
}
