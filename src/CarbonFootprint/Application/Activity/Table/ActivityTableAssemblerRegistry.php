<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Table;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class ActivityTableAssemblerRegistry
{
    /** @var array<string, ActivityTableAssembler> */
    private array $assemblers = [];

    /** @param iterable<ActivityTableAssembler> $assemblers */
    public function __construct(
        #[AutowireIterator('carbon_footprint.activity_table_assembler')]
        iterable $assemblers,
    ) {
        foreach ($assemblers as $assembler) {
            $this->assemblers[$assembler->activityType()->value] = $assembler;
        }
    }

    public function forActivityType(ActivityType $activityType): ActivityTableAssembler
    {
        return $this->assemblers[$activityType->value]
            ?? throw new \InvalidArgumentException(sprintf('No activity table assembler registered for activity type "%s".', $activityType->value));
    }
}
