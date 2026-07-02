<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprint\Step;

use App\CarbonFootprint\Application\Activity\Table\ActivityTableAssemblerRegistry;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\Unit;

final class MaterialsStepViewBuilder implements StepViewBuilder
{
    public function __construct(
        private ActivityTableAssemblerRegistry $assemblerRegistry,
        private ActivityRepository $activityRepository,
    ) {}

    public function build(EventCarbonFootprint $eventCarbonFootprint): array
    {
        $materialAssembler = $this->assemblerRegistry->forActivityType(ActivityType::Material);
        $cateringItemAssembler = $this->assemblerRegistry->forActivityType(ActivityType::CateringItem);
        $merchandisingItemAssembler = $this->assemblerRegistry->forActivityType(ActivityType::MerchandisingItem);

        return [
            'eventCarbonFootprint' => $eventCarbonFootprint,
            'activityRows' => [
                ActivityType::Material->value => $materialAssembler->buildRows($eventCarbonFootprint, $this->activityRepository),
                ActivityType::CateringItem->value => $cateringItemAssembler->buildRows($eventCarbonFootprint, $this->activityRepository),
                ActivityType::MerchandisingItem->value => $merchandisingItemAssembler->buildRows($eventCarbonFootprint, $this->activityRepository),
            ],
            'materials' => $materialAssembler->loadCatalog($eventCarbonFootprint),
            'cateringItems' => $cateringItemAssembler->loadCatalog($eventCarbonFootprint),
            'merchandisingItems' => $merchandisingItemAssembler->loadCatalog($eventCarbonFootprint),
            'units' => Unit::cases(),
            'conditions' => ItemCondition::cases(),
        ];
    }
}
