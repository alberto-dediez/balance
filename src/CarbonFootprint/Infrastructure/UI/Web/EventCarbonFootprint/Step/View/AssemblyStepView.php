<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View;

use App\CarbonFootprint\Application\EventCarbonFootprint\GetPhase\GetPhaseResponse;
use App\CarbonFootprint\Application\EventCarbonFootprint\GetPhase\PhaseSection;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\Unit;

final class AssemblyStepView
{
    public function __construct(private ActivityRowViewFactoryRegistry $rowViewFactories) {}

    /** @return array<string, mixed> */
    public function build(EventCarbonFootprint $eventCarbonFootprint, GetPhaseResponse $response): array
    {
        $combustibles = $this->section($response, 'combustibles');
        $roadTransports = $this->section($response, 'roadTransports');
        $machineryFuels = $this->section($response, 'machineryFuels');
        $machineryElectricity = $this->section($response, 'machineryElectricity');
        $buildingElectricity = $this->section($response, 'buildingElectricity');
        $overnightStays = $this->section($response, 'overnightStays');
        $mobilities = $this->section($response, 'mobilities');
        $wastes = $this->section($response, 'wastes');

        return [
            'eventCarbonFootprint' => $eventCarbonFootprint,
            'combustibles' => $combustibles->catalog,
            'roadTransports' => $roadTransports->catalog,
            'machineryFuels' => $machineryFuels->catalog,
            'electricityMarketers' => $machineryElectricity->catalog,
            'gdOFactors' => $machineryElectricity->secondaryCatalog,
            'mobilities' => $mobilities->catalog,
            'overnightStays' => $overnightStays->catalog,
            'wastes' => $wastes->catalog,
            'units' => Unit::cases(),
            'combustibleRows' => $this->rows($combustibles),
            'roadTransportRows' => $this->rows($roadTransports),
            'machineryFuelRows' => $this->rows($machineryFuels),
            'machineryElectricityRows' => $this->rows($machineryElectricity),
            'buildingElectricityRows' => $this->rows($buildingElectricity),
            'overnightStayRows' => $this->rows($overnightStays),
            'mobilityRows' => $this->rows($mobilities),
            'wasteRows' => $this->rows($wastes),
        ];
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
