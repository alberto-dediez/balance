<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\TripCalculation\Register;

use App\CarbonFootprint\Application\Activity\ActivityEmissionsResolver;
use App\CarbonFootprint\Application\Activity\EmissionFactorSelectorRegistry;
use App\CarbonFootprint\Application\Activity\EmissionsCalculator;
use App\CarbonFootprint\Application\Activity\MobilityEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\OvernightStayEmissionFactorSelector;
use App\CarbonFootprint\Application\Trip\LegInput;
use App\CarbonFootprint\Application\Trip\PropertyLocator;
use App\CarbonFootprint\Application\Trip\TripEmissionsCalculator;
use App\CarbonFootprint\Application\TripCalculation\Register\RegisterTripCalculationRequest;
use App\CarbonFootprint\Application\TripCalculation\Register\RegisterTripCalculationService;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Geo\GreatCircleRouteDistanceCalculator;
use App\CarbonFootprint\Domain\Model\Geo\TravelMode;
use App\CarbonFootprint\Domain\Model\Mobility\Mobility;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Trip\TripLegsAccumulator;
use App\CarbonFootprint\Domain\Model\Trip\TripLegsResolver;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkTokenInvalidException;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEmissionFactorGroupRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryMobilityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryOvernightStayRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryTripCalculationLinkRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryTripCalculationRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyInfo;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Shared\Domain\Model\Geo\InMemoryPlaceFinder;
use PHPUnit\Framework\TestCase;

class RegisterTripCalculationServiceTest extends TestCase
{
    private InMemoryTripCalculationLinkRepository $tripCalculationLinkRepository;

    private InMemoryTripCalculationRepository $tripCalculationRepository;

    private InMemoryMobilityRepository $mobilityRepository;

    private InMemoryOvernightStayRepository $overnightStayRepository;

    private InMemoryPropertyRepository $propertyRepository;

    private InMemoryPlaceFinder $placeFinder;

    private EmissionFactorGroupId $emissionFactorGroupId;

    private int $calculationYear;

    private RegisterTripCalculationService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->tripCalculationLinkRepository = new InMemoryTripCalculationLinkRepository();
        $this->tripCalculationRepository = new InMemoryTripCalculationRepository();
        $this->mobilityRepository = new InMemoryMobilityRepository();
        $this->overnightStayRepository = new InMemoryOvernightStayRepository();
        $this->propertyRepository = new InMemoryPropertyRepository();
        $this->placeFinder = new InMemoryPlaceFinder();

        $emissionFactorGroupRepository = new InMemoryEmissionFactorGroupRepository();
        $emissionFactorGroup = EmissionFactorGroupMother::default();
        $emissionFactorGroupRepository->add($emissionFactorGroup);

        $this->emissionFactorGroupId = $emissionFactorGroup->id();
        $this->calculationYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');

        $activityEmissionsResolver = new ActivityEmissionsResolver(
            new EmissionFactorSelectorRegistry([
                new MobilityEmissionFactorSelector($this->mobilityRepository),
                new OvernightStayEmissionFactorSelector($this->overnightStayRepository),
            ]),
            new EmissionsCalculator(),
        );

        $this->sut = new RegisterTripCalculationService(
            $this->tripCalculationLinkRepository,
            $this->tripCalculationRepository,
            $this->mobilityRepository,
            $this->propertyRepository,
            new PropertyLocator(),
            $this->placeFinder,
            new TripLegsResolver(new GreatCircleRouteDistanceCalculator()),
            new TripLegsAccumulator(),
            new TripEmissionsCalculator($activityEmissionsResolver, $this->overnightStayRepository),
            $emissionFactorGroupRepository,
        );
    }

    public function testRegistersACalculationAndReturnsItsEmissions(): void
    {
        $link = $this->linkToLocatedProperty();
        $mobility = $this->mobilityWithFactor('Coche', '0.2');
        $this->overnightStayWithFactor('4.0');

        $this->placeFinder->add('sevilla', 'Sevilla, España', 37.3891, -5.9845);

        $response = ($this->sut)(new RegisterTripCalculationRequest(
            token: $link->token(),
            originPlaceReference: 'sevilla',
            roundTrip: false,
            trips: 1,
            nights: 2,
            legs: [new LegInput(mobilityId: (string) $mobility->id(), vehicleOccupants: 1, manualDistanceKm: 100.0)],
        ));

        $stored = $this->tripCalculationRepository->find($response->tripCalculationId);

        $this->assertNotNull($stored);
        $this->assertSame(1, $stored->trips());
        $this->assertSame(2, $stored->nights());
        $this->assertCount(1, $stored->legs());
        $this->assertSame('Sevilla, España', $stored->originLabel());
        $this->assertFalse($response->overnightStayCatalogMissing);

        // Both ends are geolocated, so the route between them wins over the declared kilometres.
        $distanceKm = $stored->legs()[0]->distanceKm();
        $this->assertNotNull($distanceKm);

        $this->assertEqualsWithDelta(0.2 * $distanceKm, (float) $response->mobilityEmissions->co2eq(), 0.001);
        $this->assertEqualsWithDelta(8.0, (float) $response->overnightEmissions->co2eq(), 0.001);
        $this->assertEqualsWithDelta(0.2 * $distanceKm + 8.0, (float) $response->emissions->co2eq(), 0.001);
    }

    public function testDoublesTheDistanceOfARoundTrip(): void
    {
        $link = $this->linkToLocatedProperty();
        $mobility = $this->mobilityWithFactor('Coche', '0.2');

        $response = ($this->sut)($this->request(
            token: $link->token(),
            roundTrip: true,
            legs: [new LegInput(mobilityId: (string) $mobility->id(), manualDistanceKm: 100.0)],
        ));

        $this->assertEqualsWithDelta(40.0, (float) $response->mobilityEmissions->co2eq(), 0.001);
    }

    public function testFlagsTheMissingOvernightStayCatalog(): void
    {
        $link = $this->linkToLocatedProperty();
        $mobility = $this->mobilityWithFactor('Coche', '0.2');

        $response = ($this->sut)($this->request(
            token: $link->token(),
            nights: 3,
            legs: [new LegInput(mobilityId: (string) $mobility->id(), manualDistanceKm: 100.0)],
        ));

        $this->assertTrue($response->overnightStayCatalogMissing);
        $this->assertEqualsWithDelta(0.0, (float) $response->overnightEmissions->co2eq(), 0.001);
        $this->assertEqualsWithDelta(20.0, (float) $response->emissions->co2eq(), 0.001);
    }

    public function testThrowsWhenTheTokenIsInvalid(): void
    {
        $this->expectException(TripCalculationLinkTokenInvalidException::class);

        ($this->sut)($this->request(token: 'invalid-token'));
    }

    public function testRegistersALocalVisitWithoutLegs(): void
    {
        $link = $this->linkToLocatedProperty();

        $response = ($this->sut)($this->request(token: $link->token(), originPlaceReference: 'sevilla', legs: []));

        $stored = $this->tripCalculationRepository->find($response->tripCalculationId);

        $this->assertNotNull($stored);
        $this->assertSame([], $stored->legs());
        $this->assertNull($stored->originLabel());
        $this->assertEqualsWithDelta(0.0, (float) $response->emissions->co2eq(), 0.001);
    }

    public function testThrowsWhenTheOccupantsAreLessThanOne(): void
    {
        $link = $this->linkToLocatedProperty();
        $mobility = $this->mobilityWithFactor('Coche', '0.2');

        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)($this->request(
            token: $link->token(),
            legs: [new LegInput(mobilityId: (string) $mobility->id(), vehicleOccupants: 0, manualDistanceKm: 100.0)],
        ));
    }

    public function testThrowsWhenTheNumberOfTripsIsLessThanOne(): void
    {
        $link = $this->linkToLocatedProperty();
        $mobility = $this->mobilityWithFactor('Coche', '0.2');

        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)($this->request(
            token: $link->token(),
            trips: 0,
            legs: [new LegInput(mobilityId: (string) $mobility->id(), manualDistanceKm: 100.0)],
        ));
    }

    public function testThrowsWhenALegHasNoDistanceAtAll(): void
    {
        $link = $this->linkToUnlocatedProperty();
        $mobility = $this->mobilityWithFactor('Otro medio', '0.2', travelMode: null);

        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)($this->request(
            token: $link->token(),
            legs: [new LegInput(mobilityId: (string) $mobility->id())],
        ));
    }

    /** @param list<LegInput> $legs */
    private function request(
        string $token,
        ?string $originPlaceReference = null,
        bool $roundTrip = false,
        ?int $trips = 1,
        ?int $nights = 0,
        array $legs = [],
    ): RegisterTripCalculationRequest {
        return new RegisterTripCalculationRequest(
            token: $token,
            originPlaceReference: $originPlaceReference,
            roundTrip: $roundTrip,
            trips: $trips,
            nights: $nights,
            legs: $legs,
        );
    }

    private function linkToLocatedProperty(): TripCalculationLink
    {
        return $this->link(new PropertyInfo(latitude: '40.4680', longitude: '-3.6160'));
    }

    private function linkToUnlocatedProperty(): TripCalculationLink
    {
        return $this->link(new PropertyInfo());
    }

    private function link(PropertyInfo $info): TripCalculationLink
    {
        $property = PropertyMother::from(
            propertyId: PropertyId::generate(),
            name: 'Hotel',
            accountId: null,
            info: $info,
        );
        $this->propertyRepository->add($property);

        $link = new TripCalculationLink(TripCalculationLinkId::generate());
        $link->register(
            propertyId: $property->id(),
            accountId: AccountId::generate(),
            token: TripCalculationLink::generateToken(),
        );

        $this->tripCalculationLinkRepository->add($link);

        return $link;
    }

    private function mobilityWithFactor(string $name, string $co2eq, ?TravelMode $travelMode = TravelMode::Driving): Mobility
    {
        $mobility = null !== $travelMode
            ? MobilityMother::fromNameIdAndTravelMode($name, (string) MobilityId::generate(), $travelMode)
            : MobilityMother::fromName($name);

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
