<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View;

use App\CarbonFootprint\Application\CarbonFootprint\StageSection;
use App\CarbonFootprint\Application\EventCarbonFootprint\GetPhase\GetPhaseResponse;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\OvernightStay\CategoryOvernight;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStay;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\UI\Web\OrganizationalCarbonFootprint\View\StepExclusionsView;

final class AssemblyBreakdownStepView
{
    /** @var list<CategoryOvernight> */
    private const ALLOWED_OVERNIGHT_STAY_CATEGORIES = [CategoryOvernight::OrganizingTeam, CategoryOvernight::Suppliers];

    public function __construct(private ActivityRowViewFactoryRegistry $rowViewFactories) {}

    /** @return array<string, mixed> */
    public function build(EventCarbonFootprint $eventCarbonFootprint, GetPhaseResponse $response): array
    {
        $combustibles = $this->section($response, 'combustibles');
        $transports = $this->section($response, 'transports');
        $machineryFuels = $this->section($response, 'machineryFuels');
        $machineryElectricity = $this->section($response, 'machineryElectricity');
        $buildingElectricity = $this->section($response, 'buildingElectricity');
        $overnightStays = $this->section($response, 'overnightStays');
        $mobilities = $this->section($response, 'mobilities');
        $wastes = $this->section($response, 'wastes');

        return [
            'eventCarbonFootprint' => $eventCarbonFootprint,
            'combustibles' => $combustibles->catalog,
            'transports' => $transports->catalog,
            'machineryFuels' => $machineryFuels->catalog,
            'electricityMarketers' => $machineryElectricity->catalog,
            'gdOFactors' => $machineryElectricity->secondaryCatalog,
            'mobilities' => $mobilities->catalog,
            'overnightStays' => $this->allowedOvernightStays($overnightStays->catalog),
            'wastes' => $wastes->catalog,
            'units' => Unit::cases(),
            'combustibleRows' => $this->rows($combustibles),
            'transportRows' => $this->rows($transports),
            'machineryFuelRows' => $this->rows($machineryFuels),
            'machineryElectricityRows' => $this->rows($machineryElectricity),
            'buildingElectricityRows' => $this->rows($buildingElectricity),
            'overnightStayRows' => $this->rows($overnightStays),
            'mobilityRows' => $this->rows($mobilities),
            'wasteRows' => $this->rows($wastes),
            'exclusions' => StepExclusionsView::build($response->exclusions),
            'exclusionReasons' => ExclusionReason::cases(),
        ];
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
    private function rows(StageSection $section): array
    {
        $factory = $this->rowViewFactories->forActivityType($section->definition->activityType);

        return array_map(static fn($activity) => $factory->create($activity), $section->activities);
    }

    private function section(GetPhaseResponse $response, string $key): StageSection
    {
        foreach ($response->sections as $section) {
            if ($section->definition->key === $key) {
                return $section;
            }
        }

        throw new \InvalidArgumentException(sprintf('Section "%s" not found.', $key));
    }
}
