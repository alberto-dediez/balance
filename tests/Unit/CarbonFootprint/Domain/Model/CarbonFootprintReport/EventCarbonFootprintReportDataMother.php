<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\EventCarbonFootprintReportActivityLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\EventCarbonFootprintReportCompensation;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\EventCarbonFootprintReportCompensationLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\EventCarbonFootprintReportCompensationProjectLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\EventCarbonFootprintReportCompensationScope;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\EventCarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\EventCarbonFootprintReportExclusionLine;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\Unit;
use App\EventOperations\Domain\Model\Event\DateRange;
use App\EventOperations\Domain\Model\Event\EventSector;
use App\EventOperations\Domain\Model\Event\EventSize;
use App\EventOperations\Domain\Model\Event\EventTypology;

class EventCarbonFootprintReportDataMother
{
    /** @param list<EventCarbonFootprintReportExclusionLine> $exclusionLines */
    public static function random(array $exclusionLines = []): EventCarbonFootprintReportData
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
            exclusionLines: $exclusionLines,
        );
    }

    public static function withCompensation(): EventCarbonFootprintReportData
    {
        $emissionsBySource = [];
        foreach (Source::cases() as $source) {
            $emissionsBySource[$source->value] = new Emissions();
        }
        $emissionsBySource[Source::Materials->value] = new Emissions('1200', '1200', '0', '0');
        $emissionsBySource[Source::BuildingElectricity->value] = new Emissions('800', '800', '0', '0');
        $emissionsBySource[Source::AttendeesMobility->value] = new Emissions('6500', '6400', '50', '50');

        $emissionsByPhase = [];
        $emissionsByPhaseAndSource = [];
        foreach (Phase::cases() as $phase) {
            $emissionsByPhase[$phase->value] = new Emissions();
            $emissionsByPhaseAndSource[$phase->value] = [];
            foreach (Source::cases() as $source) {
                $emissionsByPhaseAndSource[$phase->value][$source->value] = new Emissions();
            }
        }
        $emissionsByPhase[Phase::Materials->value] = new Emissions('1200', '1200', '0', '0');
        $emissionsByPhase[Phase::Assembly->value] = new Emissions('300', '300', '0', '0');
        $emissionsByPhase[Phase::Celebration->value] = new Emissions('7000', '6900', '50', '50');
        $emissionsByPhaseAndSource[Phase::Materials->value][Source::Materials->value] = new Emissions('1200', '1200', '0', '0');
        $emissionsByPhaseAndSource[Phase::Assembly->value][Source::BuildingElectricity->value] = new Emissions('300', '300', '0', '0');
        $emissionsByPhaseAndSource[Phase::Celebration->value][Source::BuildingElectricity->value] = new Emissions('500', '500', '0', '0');
        $emissionsByPhaseAndSource[Phase::Celebration->value][Source::AttendeesMobility->value] = new Emissions('6500', '6400', '50', '50');

        return new EventCarbonFootprintReportData(
            eventName: 'Feria Verde 2026',
            sector: EventSector::Business,
            typology: EventTypology::Local,
            size: EventSize::Small,
            estimatedAttendees: 850,
            assembly: new DateRange(new \DateTimeImmutable('2026-05-10'), new \DateTimeImmutable('2026-05-11')),
            celebration: new DateRange(new \DateTimeImmutable('2026-05-12'), new \DateTimeImmutable('2026-05-14')),
            breakdown: new DateRange(new \DateTimeImmutable('2026-05-15'), new \DateTimeImmutable('2026-05-15')),
            emissionFactorGroupName: 'MITECO 2026',
            calculationYear: 2026,
            totalEmissions: new Emissions('8500', '8400', '50', '50'),
            emissionsPerAttendee: new Emissions('10', '9.88', '0.06', '0.06'),
            emissionsBySource: $emissionsBySource,
            emissionsByPhase: $emissionsByPhase,
            emissionsByPhaseAndSource: $emissionsByPhaseAndSource,
            activityLines: [
                new EventCarbonFootprintReportActivityLine('Estructura de stands de madera', ActivityType::Material, Phase::Materials, Source::Materials, 1500.0, Unit::Kilogram, '0.8', Unit::Kilogram, 2026, 'MITECO', '1200'),
                new EventCarbonFootprintReportActivityLine('Comercializadora Verde', ActivityType::Electricity, Phase::Assembly, Source::BuildingElectricity, 1200.0, Unit::KWh, '0.25', Unit::KWh, 2026, 'Comercializadora', '300'),
                new EventCarbonFootprintReportActivityLine('Comercializadora Verde', ActivityType::Electricity, Phase::Celebration, Source::BuildingElectricity, 2000.0, Unit::KWh, '0.25', Unit::KWh, 2026, 'Comercializadora', '500'),
                new EventCarbonFootprintReportActivityLine('Coche particular', ActivityType::Mobility, Phase::Celebration, Source::AttendeesMobility, 26000.0, Unit::Kilometer, '0.25', Unit::Kilometer, 2026, 'MITECO', '6500'),
            ],
            exclusionLines: [
                new EventCarbonFootprintReportExclusionLine(Phase::Assembly, Source::WasteManagement, ExclusionReason::NoDataAvailable, 'Sin facturas de gestión de residuos'),
                new EventCarbonFootprintReportExclusionLine(Phase::Breakdown, Source::FreightTransport, ExclusionReason::NotSignificant, null),
            ],
            compensation: new EventCarbonFootprintReportCompensation(
                ownTonnes: 4.0,
                attendeesTonnes: 1.5,
                totalTonnes: 5.5,
                coveragePercentage: 64.7,
                ownContributions: 1,
                attendeesContributions: 3,
                cutOffAt: new \DateTimeImmutable('2026-06-01'),
                lastCompensatedAt: new \DateTimeImmutable('2026-05-30'),
                projectLines: [
                    new EventCarbonFootprintReportCompensationProjectLine(EventCarbonFootprintReportCompensationScope::OwnCompany, 'Reforestación Sierra Norte', 'Madrid, España', 'MITECO-2024-0001', true, 1, 4.0),
                    new EventCarbonFootprintReportCompensationProjectLine(EventCarbonFootprintReportCompensationScope::Attendees, 'Reforestación Sierra Norte', 'Madrid, España', 'MITECO-2024-0001', true, 3, 1.5),
                ],
                traceabilityLines: [
                    new EventCarbonFootprintReportCompensationLine(EventCarbonFootprintReportCompensationScope::OwnCompany, 'Acme SL', 'CMP-0001-0001', new \DateTimeImmutable('2026-05-20'), 'Reforestación Sierra Norte', 'MITECO-2024-0001', 4.0, ['2024-b012'], [['serial' => '2024-b012', 'startTonnes' => 10.0, 'endTonnes' => 14.0]], 'https://example.com/verify/abc'),
                    new EventCarbonFootprintReportCompensationLine(EventCarbonFootprintReportCompensationScope::Attendees, null, 'ATT-0001-0002', new \DateTimeImmutable('2026-05-30'), 'Reforestación Sierra Norte', 'MITECO-2024-0001', 0.5, [], [], 'https://example.com/verify/def'),
                ],
            ),
        );
    }
}
