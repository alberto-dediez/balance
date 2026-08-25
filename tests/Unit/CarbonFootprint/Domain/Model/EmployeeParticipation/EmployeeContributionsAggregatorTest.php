<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\EmployeeParticipation;

use App\CarbonFootprint\Domain\Model\EmployeeParticipation\EmployeeContributionsAggregator;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\Mobility\FactorBasis;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionMother;
use PHPUnit\Framework\TestCase;

class EmployeeContributionsAggregatorTest extends TestCase
{
    private EmployeeContributionsAggregator $sut;

    protected function setUp(): void
    {
        $this->sut = new EmployeeContributionsAggregator();
    }

    public function testAggregatesKmAndNightsByPhase(): void
    {
        $eventCarbonFootprintId = (string) EventCarbonFootprintId::generate();
        $mobilityId = (string) MobilityId::generate();

        $contribution = EmployeeContributionMother::answeredForAllPhases(
            eventCarbonFootprintId: $eventCarbonFootprintId,
            mobilityId: $mobilityId,
            distanceKm: 100.0,
            vehicleOccupants: 2,
            days: 4,
            hotelNights: 3,
        );

        $aggregation = $this->sut->aggregate([$contribution], 1);

        foreach ([Phase::Assembly, Phase::Celebration, Phase::Breakdown] as $phase) {
            $this->assertSame(400.0, $aggregation->kmByMobilityId($phase)[$mobilityId]);
            $this->assertSame(3.0, $aggregation->hotelNights($phase));
        }
    }

    public function testAppliesExtrapolationFactor(): void
    {
        $eventCarbonFootprintId = (string) EventCarbonFootprintId::generate();
        $mobilityId = (string) MobilityId::generate();

        $contributions = [
            EmployeeContributionMother::answeredForAllPhases($eventCarbonFootprintId, $mobilityId, distanceKm: 100.0, hotelNights: 1),
            EmployeeContributionMother::answeredForAllPhases($eventCarbonFootprintId, $mobilityId, distanceKm: 300.0, hotelNights: 3),
        ];

        $aggregation = $this->sut->aggregate($contributions, 10);

        $this->assertSame(2000.0, $aggregation->kmByMobilityId(Phase::Celebration)[$mobilityId]);
        $this->assertSame(20.0, $aggregation->hotelNights(Phase::Celebration));
    }

    public function testSkipsPhasesNotWorked(): void
    {
        $eventCarbonFootprintId = (string) EventCarbonFootprintId::generate();
        $mobilityId = (string) MobilityId::generate();

        $contribution = EmployeeContributionMother::fromEventCarbonFootprintIdAndAccount($eventCarbonFootprintId, 'account-id');
        $contribution->answerPhase(
            phase: Phase::Assembly,
            worked: true,
            mobilityId: new MobilityId($mobilityId),
            distanceKm: 50.0,
            vehicleOccupants: 1,
            days: 2,
            hotelNights: 1,
        );
        $contribution->answerPhase(
            phase: Phase::Celebration,
            worked: false,
            mobilityId: new MobilityId($mobilityId),
            distanceKm: 50.0,
            vehicleOccupants: 1,
            days: 2,
            hotelNights: 1,
        );

        $aggregation = $this->sut->aggregate([$contribution], 1);

        $this->assertSame(100.0, $aggregation->kmByMobilityId(Phase::Assembly)[$mobilityId]);
        $this->assertSame([], $aggregation->kmByMobilityId(Phase::Celebration));
        $this->assertSame(0.0, $aggregation->hotelNights(Phase::Celebration));
    }

    public function testDividesByVehicleOccupantsWhenTheFactorIsPerVehicle(): void
    {
        $eventCarbonFootprintId = (string) EventCarbonFootprintId::generate();
        $mobilityId = (string) MobilityId::generate();

        $contribution = EmployeeContributionMother::answeredForAllPhases(
            eventCarbonFootprintId: $eventCarbonFootprintId,
            mobilityId: $mobilityId,
            distanceKm: 90.0,
            vehicleOccupants: 3,
            days: 1,
        );

        $aggregation = $this->sut->aggregate([$contribution], 1, [$mobilityId => FactorBasis::PerVehicle]);

        $this->assertSame(30.0, $aggregation->kmByMobilityId(Phase::Assembly)[$mobilityId]);
    }

    public function testIgnoresVehicleOccupantsWhenTheFactorIsPerPassenger(): void
    {
        $eventCarbonFootprintId = (string) EventCarbonFootprintId::generate();
        $mobilityId = (string) MobilityId::generate();

        $contribution = EmployeeContributionMother::answeredForAllPhases(
            eventCarbonFootprintId: $eventCarbonFootprintId,
            mobilityId: $mobilityId,
            distanceKm: 90.0,
            vehicleOccupants: 3,
            days: 1,
        );

        $aggregation = $this->sut->aggregate([$contribution], 1, [$mobilityId => FactorBasis::PerPassenger]);

        $this->assertSame(90.0, $aggregation->kmByMobilityId(Phase::Assembly)[$mobilityId]);
    }

    public function testReturnsEmptyAggregationWithoutContributions(): void
    {
        $aggregation = $this->sut->aggregate([], 10);

        foreach ([Phase::Assembly, Phase::Celebration, Phase::Breakdown] as $phase) {
            $this->assertSame([], $aggregation->kmByMobilityId($phase));
            $this->assertSame(0.0, $aggregation->hotelNights($phase));
        }
    }
}
