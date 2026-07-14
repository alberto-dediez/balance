<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportActivityLine;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\Unit;
use App\EventOperations\Domain\Model\Event\DateRange;
use App\EventOperations\Domain\Model\Event\EventSector;
use App\EventOperations\Domain\Model\Event\EventSize;
use App\EventOperations\Domain\Model\Event\EventTypology;

class EventCarbonFootprintReportDataMother
{
    public static function random(): EventCarbonFootprintReportData
    {
        $emissionsBySource = [];
        foreach (Source::cases() as $source) {
            $emissionsBySource[$source->value] = new Emissions();
        }
        $emissionsBySource[Source::Materials->value] = new Emissions('10', '10', '0', '0');

        $emissionsByPhase = [];
        $emissionsByPhaseAndSource = [];
        foreach (Phase::cases() as $phase) {
            $emissionsByPhase[$phase->value] = new Emissions();
            $emissionsByPhaseAndSource[$phase->value] = [];
            foreach (Source::cases() as $source) {
                $emissionsByPhaseAndSource[$phase->value][$source->value] = new Emissions();
            }
        }
        $emissionsByPhase[Phase::Materials->value] = new Emissions('10', '10', '0', '0');
        $emissionsByPhaseAndSource[Phase::Materials->value][Source::Materials->value] = new Emissions('10', '10', '0', '0');

        return new EventCarbonFootprintReportData(
            eventName: 'Test Event',
            sector: EventSector::Business,
            typology: EventTypology::Local,
            size: EventSize::Small,
            estimatedAttendees: 100,
            assembly: new DateRange(),
            celebration: new DateRange(),
            breakdown: new DateRange(),
            emissionFactorGroupName: 'Estándar',
            calculationYear: 2026,
            totalEmissions: new Emissions('10', '10', '0', '0'),
            emissionsPerAttendee: new Emissions('0.1', '0.1', '0', '0'),
            emissionsBySource: $emissionsBySource,
            emissionsByPhase: $emissionsByPhase,
            emissionsByPhaseAndSource: $emissionsByPhaseAndSource,
            activityLines: [
                new EventCarbonFootprintReportActivityLine(
                    itemName: 'Material de ejemplo',
                    activityType: ActivityType::Material,
                    phase: Phase::Materials,
                    source: Source::Materials,
                    quantity: 10.0,
                    unit: Unit::Kilogram,
                    factorCo2eq: '1',
                    factorUnit: Unit::Kilogram,
                    factorYear: 2026,
                    factorSource: 'MITECO',
                    emissionsCo2eq: '10',
                ),
            ],
        );
    }
}
