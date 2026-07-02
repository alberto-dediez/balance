<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprint\Step;

use App\CarbonFootprint\Application\Activity\Table\ActivityTableAssemblerRegistry;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorFilter;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepository;
use App\CarbonFootprint\Domain\Model\Unit;

final class AssemblyStepViewBuilder implements StepViewBuilder
{
    public function __construct(
        private ActivityTableAssemblerRegistry $assemblerRegistry,
        private ActivityRepository $activityRepository,
        private GdOFactorRepository $gdOFactorRepository,
    ) {}

    public function build(EventCarbonFootprint $eventCarbonFootprint): array
    {
        $combustibleAssembler = $this->assemblerRegistry->forActivityType(ActivityType::Combustible);
        $roadTransportAssembler = $this->assemblerRegistry->forActivityType(ActivityType::RoadTransport);
        $machineryFuelAssembler = $this->assemblerRegistry->forActivityType(ActivityType::MachineryFuel);
        $electricityAssembler = $this->assemblerRegistry->forActivityType(ActivityType::Electricity);
        $mobilityAssembler = $this->assemblerRegistry->forActivityType(ActivityType::Mobility);
        $overnightStayAssembler = $this->assemblerRegistry->forActivityType(ActivityType::OvernightStay);
        $wasteAssembler = $this->assemblerRegistry->forActivityType(ActivityType::Waste);

        return [
            'eventCarbonFootprint' => $eventCarbonFootprint,
            'combustibles' => $combustibleAssembler->loadCatalog($eventCarbonFootprint),
            'roadTransports' => $roadTransportAssembler->loadCatalog($eventCarbonFootprint),
            'machineryFuels' => $machineryFuelAssembler->loadCatalog($eventCarbonFootprint),
            'electricityMarketers' => $electricityAssembler->loadCatalog($eventCarbonFootprint),
            'gdOFactors' => $this->gdOFactorRepository->findByFilter(new GdOFactorFilter(limit: 0)),
            'mobilities' => $mobilityAssembler->loadCatalog($eventCarbonFootprint),
            'overnightStays' => $overnightStayAssembler->loadCatalog($eventCarbonFootprint),
            'wastes' => $wasteAssembler->loadCatalog($eventCarbonFootprint),
            'units' => Unit::cases(),
            'combustibleRows' => $combustibleAssembler->buildRows($eventCarbonFootprint, $this->activityRepository, Source::FixedFacilities),
            'roadTransportRows' => $roadTransportAssembler->buildRows($eventCarbonFootprint, $this->activityRepository, Source::FreightTransport),
            'machineryFuelRows' => $machineryFuelAssembler->buildRows($eventCarbonFootprint, $this->activityRepository, Source::MachineryOperation),
            'machineryElectricityRows' => $electricityAssembler->buildRows($eventCarbonFootprint, $this->activityRepository, Source::MachineryOperation),
            'buildingElectricityRows' => $electricityAssembler->buildRows($eventCarbonFootprint, $this->activityRepository, Source::BuildingElectricity),
            'overnightStayRows' => $overnightStayAssembler->buildRows($eventCarbonFootprint, $this->activityRepository, Source::SuppliersAndStaffAccommodation),
            'mobilityRows' => $mobilityAssembler->buildRows($eventCarbonFootprint, $this->activityRepository, Source::SuppliersAndStaffMobility),
            'wasteRows' => $wasteAssembler->buildRows($eventCarbonFootprint, $this->activityRepository, Source::WasteManagement),
        ];
    }
}
