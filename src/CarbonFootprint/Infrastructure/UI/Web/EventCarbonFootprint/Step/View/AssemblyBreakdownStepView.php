<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View;

use App\CarbonFootprint\Application\EventCarbonFootprint\GetPhase\GetPhaseResponse;
use App\CarbonFootprint\Application\EventCarbonFootprint\GetPhase\PhaseSection;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\OvernightStay\CategoryOvernight;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStay;
use App\CarbonFootprint\Domain\Model\Unit;

final class AssemblyBreakdownStepView
{
    /** @var list<CategoryOvernight> */
    private const ALLOWED_OVERNIGHT_STAY_CATEGORIES = [CategoryOvernight::OrganizingTeam, CategoryOvernight::Suppliers];

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
            'overnightStays' => $this->allowedOvernightStays($overnightStays->catalog),
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

    /**
     * @param list<object> $catalog
     *
     * @return list<OvernightStay>
     */
    private function allowedOvernightStays(array $catalog): array
    {
        return array_values(array_filter(
            $catalog,
            static fn(object $overnightStay): bool => $overnightStay instanceof OvernightStay
                && in_array($overnightStay->category(), self::ALLOWED_OVERNIGHT_STAY_CATEGORIES, true),
        ));
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
