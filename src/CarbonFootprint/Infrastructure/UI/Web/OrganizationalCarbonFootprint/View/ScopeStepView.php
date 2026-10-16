<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\OrganizationalCarbonFootprint\View;

use App\CarbonFootprint\Application\CarbonFootprint\StageSection;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\ActivityRowView;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\ActivityRowViewFactoryRegistry;

final class ScopeStepView
{
    public function __construct(private ActivityRowViewFactoryRegistry $rowViewFactories) {}

    /**
     * @param list<StageSection> $sections
     *
     * @return array<string, mixed>
     */
    public function build(array $sections): array
    {
        $combustibles = $this->section($sections, 'combustibles');
        $vehicles = $this->section($sections, 'vehicles');
        $refrigerantGases = $this->section($sections, 'refrigerantGases');
        $electricity = $this->section($sections, 'electricity');

        return [
            'combustibles' => null === $combustibles ? [] : $combustibles->catalog,
            'transports' => null === $vehicles ? [] : $vehicles->catalog,
            'refrigerantGases' => null === $refrigerantGases ? [] : $refrigerantGases->catalog,
            'electricityMarketers' => null === $electricity ? [] : $electricity->catalog,
            'gdOFactors' => null === $electricity ? [] : $electricity->secondaryCatalog,
            'units' => Unit::cases(),
            'combustibleRows' => $this->rows($combustibles),
            'transportRows' => $this->rows($vehicles),
            'refrigerantGasRows' => $this->rows($refrigerantGases),
            'electricityRows' => $this->rows($electricity),
        ];
    }

    /** @return list<ActivityRowView> */
    private function rows(?StageSection $section): array
    {
        if (null === $section) {
            return [];
        }

        $factory = $this->rowViewFactories->forActivityType($section->definition->activityType);

        return array_map(static fn($activity) => $factory->create($activity), $section->activities);
    }

    /** @param list<StageSection> $sections */
    private function section(array $sections, string $key): ?StageSection
    {
        foreach ($sections as $section) {
            if ($section->definition->key === $key) {
                return $section;
            }
        }

        return null;
    }
}
