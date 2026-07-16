<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View;

use App\CarbonFootprint\Application\EventCarbonFootprint\GetPhase\GetPhaseResponse;
use App\CarbonFootprint\Application\EventCarbonFootprint\GetPhase\PhaseSection;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\Unit;

final class MaterialsStepView
{
    public function __construct(private ActivityRowViewFactoryRegistry $rowViewFactories) {}

    /** @return array<string, mixed> */
    public function build(EventCarbonFootprint $eventCarbonFootprint, GetPhaseResponse $response): array
    {
        $materials = $this->section($response, 'materials');
        $cateringItems = $this->section($response, 'cateringItems');
        $merchandisingItems = $this->section($response, 'merchandisingItems');

        return [
            'eventCarbonFootprint' => $eventCarbonFootprint,
            'activityRows' => [
                ActivityType::Material->value => $this->rows($materials),
                ActivityType::CateringItem->value => $this->rows($cateringItems),
                ActivityType::MerchandisingItem->value => $this->rows($merchandisingItems),
            ],
            'materials' => $materials->catalog,
            'cateringItems' => $cateringItems->catalog,
            'merchandisingItems' => $merchandisingItems->catalog,
            'units' => Unit::cases(),
            'conditions' => ItemCondition::cases(),
            'exclusions' => $this->exclusions($response),
            'exclusionReasons' => ExclusionReason::cases(),
        ];
    }

    /** @return array<string, array{reason: string, comment: ?string}> */
    private function exclusions(GetPhaseResponse $response): array
    {
        $exclusions = [];
        foreach ($response->exclusions as $sourceValue => $exclusion) {
            $exclusions[$sourceValue] = [
                'reason' => $exclusion->reason()->value,
                'comment' => $exclusion->comment(),
            ];
        }

        return $exclusions;
    }

    /** @return list<ActivityRowView> */
    private function rows(PhaseSection $section): array
    {
        $factory = $this->rowViewFactories->forActivityType($section->definition->activityType);

        return array_map(static fn($activity) => $factory->create($activity), $section->activities);
    }

    private function section(GetPhaseResponse $response, string $key): PhaseSection
    {
        foreach ($response->sections as $section) {
            if ($section->definition->key === $key) {
                return $section;
            }
        }

        throw new \InvalidArgumentException(sprintf('Section "%s" not found.', $key));
    }
}
