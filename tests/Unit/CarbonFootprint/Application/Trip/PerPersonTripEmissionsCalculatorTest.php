<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Trip;

use App\CarbonFootprint\Application\Activity\ActivityEmissionsResolver;
use App\CarbonFootprint\Application\Activity\EmissionFactorSelectorRegistry;
use App\CarbonFootprint\Application\Activity\MobilityEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\OvernightStayEmissionFactorSelector;
use App\CarbonFootprint\Application\Mobility\MobilityFactorBasisProvider;
use App\CarbonFootprint\Application\Trip\PerPersonTripEmissionsCalculator;
use App\CarbonFootprint\Application\Trip\TripEmissionsCalculator;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Geo\TravelMode;
use App\CarbonFootprint\Domain\Model\Mobility\FactorBasis;
use App\CarbonFootprint\Domain\Model\Mobility\Mobility;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Trip\LegKind;
use App\CarbonFootprint\Domain\Model\Trip\TripLegsAccumulator;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryMobilityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryOvernightStayRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\Activity\EmissionsCalculatorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkMother;
use PHPUnit\Framework\TestCase;

class PerPersonTripEmissionsCalculatorTest extends TestCase
{
    private InMemoryMobilityRepository $mobilityRepository;

    private InMemoryOvernightStayRepository $overnightStayRepository;

    private EmissionFactorGroupId $emissionFactorGroupId;

    private int $calculationYear;

    private PerPersonTripEmissionsCalculator $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->mobilityRepository = new InMemoryMobilityRepository();
        $this->overnightStayRepository = new InMemoryOvernightStayRepository();
        $this->emissionFactorGroupId = EmissionFactorGroupMother::default()->id();
        $this->calculationYear = 2026;

        $this->sut = new PerPersonTripEmissionsCalculator(
            new TripLegsAccumulator(),
            new MobilityFactorBasisProvider($this->mobilityRepository),
            new TripEmissionsCalculator(
                new ActivityEmissionsResolver(
                    new EmissionFactorSelectorRegistry([
                        new MobilityEmissionFactorSelector($this->mobilityRepository),
                        new OvernightStayEmissionFactorSelector($this->overnightStayRepository),
                    ]),
                    EmissionsCalculatorMother::create(),
                ),
                $this->overnightStayRepository,
            ),
        );
    }

    public function testSplitsTheKilometresOfAVehicleAndTheNightsOfARoomBetweenTheirOccupants(): void
    {
        $car = $this->mobilityWithFactor('Coche', '0.2', FactorBasis::PerVehicle);
        $this->overnightStayWithFactor('10');

        $response = AttendeeSurveyResponseMother::from(
            attendeeSurveyId: (string) AttendeeSurveyMother::registeredAttendeeSurvey()->id(),
            cameFromAnotherCity: true,
            intercityTrips: 1,
            usedHotel: true,
            hotelNights: 2,
            hotelPeople: 4,
            legs: [AttendeeSurveyResponseMother::leg(1, LegKind::Intercity, (string) $car->id(), 100.0, 4)],
            emissionFactorGroupId: $this->emissionFactorGroupId,
            calculationYear: $this->calculationYear,
        );

        $emissions = $this->sut->forAttendeeSurveyResponses([$response]);

        $this->assertEqualsWithDelta(25.0, (float) $emissions->co2eq(), 0.001);
    }

    public function testKeepsTheKilometresOfEachOccupantWhenTheFactorIsPerPassenger(): void
    {
        $train = $this->mobilityWithFactor('Tren', '0.05', FactorBasis::PerPassenger, TravelMode::Transit);

        $response = AttendeeSurveyResponseMother::from(
            attendeeSurveyId: (string) AttendeeSurveyMother::registeredAttendeeSurvey()->id(),
            cameFromAnotherCity: true,
            intercityTrips: 1,
            intercityRoundTrip: true,
            legs: [AttendeeSurveyResponseMother::leg(1, LegKind::Intercity, (string) $train->id(), 100.0, 2)],
            emissionFactorGroupId: $this->emissionFactorGroupId,
            calculationYear: $this->calculationYear,
        );

        $emissions = $this->sut->forAttendeeSurveyResponses([$response]);

        $this->assertEqualsWithDelta(10.0, (float) $emissions->co2eq(), 0.001);
    }

    public function testAveragesThePerPersonEmissionsOfEveryTripCalculation(): void
    {
        $car = $this->mobilityWithFactor('Coche', '0.2', FactorBasis::PerVehicle);
        $linkId = (string) TripCalculationLinkMother::random()->id();

        $emissions = $this->sut->forTripCalculations([
            TripCalculationMother::from(
                tripCalculationLinkId: $linkId,
                legs: [TripCalculationMother::leg(1, (string) $car->id(), 100.0, 4)],
                emissionFactorGroupId: (string) $this->emissionFactorGroupId,
                calculationYear: $this->calculationYear,
            ),
            TripCalculationMother::from(
                tripCalculationLinkId: $linkId,
                legs: [TripCalculationMother::leg(1, (string) $car->id(), 100.0)],
                emissionFactorGroupId: (string) $this->emissionFactorGroupId,
                calculationYear: $this->calculationYear,
            ),
        ]);

        $this->assertEqualsWithDelta(12.5, (float) $emissions->co2eq(), 0.001);
    }

    public function testReturnsNoEmissionsWithoutCalculations(): void
    {
        $this->assertSame('0', $this->sut->forTripCalculations([])->co2eq());
        $this->assertSame('0', $this->sut->forAttendeeSurveyResponses([])->co2eq());
    }

    private function mobilityWithFactor(
        string $name,
        string $co2eq,
        FactorBasis $factorBasis,
        TravelMode $travelMode = TravelMode::Driving,
    ): Mobility {
        $mobility = MobilityMother::from($name, (string) MobilityId::generate(), travelMode: $travelMode, factorBasis: $factorBasis);

        $mobility->setEmissionFactor(new EmissionFactor(
            $co2eq,
            $co2eq,
            '0',
            '0',
            $this->calculationYear,
            $this->emissionFactorGroupId,
            Unit::Kilometer,
        ));

        $this->mobilityRepository->add($mobility);

        return $mobility;
    }

    private function overnightStayWithFactor(string $co2eq): void
    {
        $overnightStay = OvernightStayMother::registeredOvernightStay();

        $overnightStay->setEmissionFactor(new EmissionFactor(
            $co2eq,
            $co2eq,
            '0',
            '0',
            $this->calculationYear,
            $this->emissionFactorGroupId,
            Unit::Pernoctacion,
        ));

        $this->overnightStayRepository->add($overnightStay);
    }
}
