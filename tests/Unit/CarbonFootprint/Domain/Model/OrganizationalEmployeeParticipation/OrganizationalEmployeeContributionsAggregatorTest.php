<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\Mobility\FactorBasis;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContribution;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation\OrganizationalEmployeeContributionsAggregator;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionMother;
use PHPUnit\Framework\TestCase;

class OrganizationalEmployeeContributionsAggregatorTest extends TestCase
{
    private const string CAR = 'mobility-car';
    private const string TRAIN = 'mobility-train';
    private const string PLANE = 'mobility-plane';

    private OrganizationalEmployeeContributionsAggregator $sut;

    protected function setUp(): void
    {
        $this->sut = new OrganizationalEmployeeContributionsAggregator();
    }

    public function testThePropertyExampleIsAggregatedAndExtrapolated(): void
    {
        $aggregation = $this->sut->aggregate($this->eightAnswers(CarbonFootprintReportSubjectType::Property, [null, null, null, null, null, null, null, null]), 40, $this->factorBases(), []);

        $this->assertSame([''], $aggregation->blocks());
        $this->assertEqualsWithDelta(165600.0, $aggregation->commutingKmByMobilityId('')[self::CAR], 0.001);
        $this->assertEqualsWithDelta(110400.0, $aggregation->commutingKmByMobilityId('')[self::TRAIN], 0.001);
        $this->assertEqualsWithDelta(18000.0, $aggregation->businessTravelKmByMobilityId('')[self::PLANE], 0.001);
        $this->assertEqualsWithDelta(30.0, $aggregation->hotelNights(''), 0.001);
    }

    public function testThePropertyExampleRawKmBeforeExtrapolating(): void
    {
        $aggregation = $this->sut->aggregate($this->eightAnswers(CarbonFootprintReportSubjectType::Property, [null, null, null, null, null, null, null, null]), 8, $this->factorBases(), []);

        $this->assertEqualsWithDelta(33120.0, $aggregation->commutingKmByMobilityId('')[self::CAR], 0.001);
        $this->assertEqualsWithDelta(22080.0, $aggregation->commutingKmByMobilityId('')[self::TRAIN], 0.001);
        $this->assertEqualsWithDelta(3600.0, $aggregation->businessTravelKmByMobilityId('')[self::PLANE], 0.001);
        $this->assertEqualsWithDelta(6.0, $aggregation->hotelNights(''), 0.001);
    }

    public function testAPerVehicleTripIsDividedByItsOccupantsAndAPerPassengerOneIsNot(): void
    {
        $car = OrganizationalEmployeeContributionMother::answered(CarbonFootprintReportSubjectType::Property, 'footprint-1', null, tripMobilityId: self::CAR, tripKm: 300.0, trips: 2, tripOccupants: 3);
        $train = OrganizationalEmployeeContributionMother::answered(CarbonFootprintReportSubjectType::Property, 'footprint-1', null, tripMobilityId: self::TRAIN, tripKm: 300.0, trips: 2, tripOccupants: 3);

        $aggregation = $this->sut->aggregate([$car, $train], 2, $this->factorBases(), []);

        $this->assertEqualsWithDelta(200.0, $aggregation->businessTravelKmByMobilityId('')[self::CAR], 0.001);
        $this->assertEqualsWithDelta(600.0, $aggregation->businessTravelKmByMobilityId('')[self::TRAIN], 0.001);
    }

    public function testAPerVehicleCommuteIsDividedByItsOccupantsAndAPerPassengerOneIsNot(): void
    {
        $car = OrganizationalEmployeeContributionMother::answered(CarbonFootprintReportSubjectType::Property, 'footprint-1', self::CAR, commuteKm: 10.0, daysPerWeek: 5, weeksPerYear: 40, commuteOccupants: 2);
        $train = OrganizationalEmployeeContributionMother::answered(CarbonFootprintReportSubjectType::Property, 'footprint-1', self::TRAIN, commuteKm: 10.0, daysPerWeek: 5, weeksPerYear: 40, commuteOccupants: 2);

        $aggregation = $this->sut->aggregate([$car, $train], 2, $this->factorBases(), []);

        $this->assertEqualsWithDelta(2000.0, $aggregation->commutingKmByMobilityId('')[self::CAR], 0.001);
        $this->assertEqualsWithDelta(4000.0, $aggregation->commutingKmByMobilityId('')[self::TRAIN], 0.001);
    }

    public function testNoAnswersReturnsAnEmptyAggregation(): void
    {
        $aggregation = $this->sut->aggregate([], 40, $this->factorBases(), []);

        $this->assertSame([], $aggregation->blocks());
        $this->assertSame([], $aggregation->commutingKmByMobilityId(''));
        $this->assertSame([], $aggregation->businessTravelKmByMobilityId(''));
        $this->assertSame(0.0, $aggregation->hotelNights(''));
    }

    public function testTheCompanyExampleIsGroupedByWorkplace(): void
    {
        $workplaces = ['property-a', 'property-a', 'property-a', 'property-a', 'property-a', 'property-b', null, null];

        $aggregation = $this->sut->aggregate($this->eightAnswers(CarbonFootprintReportSubjectType::Company, $workplaces), 40, $this->factorBases(), ['property-a', 'property-b']);

        $blocks = $aggregation->blocks();
        sort($blocks);
        $this->assertSame(['', 'property-a', 'property-b'], $blocks);

        $totalCar = 0.0;
        $totalTrain = 0.0;
        $totalPlane = 0.0;
        $totalNights = 0.0;
        foreach ($blocks as $block) {
            $totalCar += $aggregation->commutingKmByMobilityId($block)[self::CAR] ?? 0.0;
            $totalTrain += $aggregation->commutingKmByMobilityId($block)[self::TRAIN] ?? 0.0;
            $totalPlane += $aggregation->businessTravelKmByMobilityId($block)[self::PLANE] ?? 0.0;
            $totalNights += $aggregation->hotelNights($block);
        }

        $this->assertEqualsWithDelta(165600.0, $totalCar, 0.001);
        $this->assertEqualsWithDelta(110400.0, $totalTrain, 0.001);
        $this->assertEqualsWithDelta(18000.0, $totalPlane, 0.001);
        $this->assertEqualsWithDelta(30.0, $totalNights, 0.001);
        $this->assertEqualsWithDelta(5 * 5520.0 * 5, $aggregation->commutingKmByMobilityId('property-a')[self::CAR], 0.001);
        $this->assertEqualsWithDelta(5520.0 * 5, $aggregation->commutingKmByMobilityId('property-b')[self::CAR], 0.001);
        $this->assertEqualsWithDelta(2 * 11040.0 * 5, $aggregation->commutingKmByMobilityId('')[self::TRAIN], 0.001);
    }

    public function testAWorkplaceThatIsNotAdmittedFallsIntoTheUnassignedBlock(): void
    {
        $contribution = OrganizationalEmployeeContributionMother::answered(CarbonFootprintReportSubjectType::Company, 'footprint-1', self::TRAIN, commuteKm: 10.0, daysPerWeek: 1, weeksPerYear: 1, workplacePropertyId: 'property-c');

        $aggregation = $this->sut->aggregate([$contribution], 1, $this->factorBases(), ['property-a']);

        $this->assertSame([''], $aggregation->blocks());
        $this->assertEqualsWithDelta(20.0, $aggregation->commutingKmByMobilityId('')[self::TRAIN], 0.001);
    }

    /**
     * @param list<?string> $workplaces
     *
     * @return list<OrganizationalEmployeeContribution>
     */
    private function eightAnswers(CarbonFootprintReportSubjectType $subjectType, array $workplaces): array
    {
        $contributions = [];

        foreach ($workplaces as $index => $workplace) {
            $byCar = $index < 6;
            $flies = in_array($index, [0, 6, 7], true);

            $contributions[] = OrganizationalEmployeeContributionMother::answered(
                subjectType: $subjectType,
                carbonFootprintId: 'footprint-1',
                commuteMobilityId: $byCar ? self::CAR : self::TRAIN,
                commuteKm: $byCar ? 12.0 : 30.0,
                daysPerWeek: $byCar ? 5 : 4,
                weeksPerYear: 46,
                tripMobilityId: $flies ? self::PLANE : null,
                tripKm: 1200.0,
                trips: 1,
                hotelNights: 2,
                workplacePropertyId: $workplace,
            );
        }

        return $contributions;
    }

    /** @return array<string, FactorBasis> */
    private function factorBases(): array
    {
        return [
            self::CAR => FactorBasis::PerVehicle,
            self::TRAIN => FactorBasis::PerPassenger,
            self::PLANE => FactorBasis::PerPassenger,
        ];
    }
}
