<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\TripCalculation\Import;

use App\CarbonFootprint\Application\TripCalculation\Import\ImportTripCalculationRequest;
use App\CarbonFootprint\Application\TripCalculation\Import\ImportTripCalculationService;
use App\CarbonFootprint\Application\TripCalculationLink\TripCalculationLinkAuthorizer;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Geo\DistanceSource;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Trip\LegKind;
use App\CarbonFootprint\Domain\Model\Trip\TripWaypoint;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryTripCalculationLinkRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryTripCalculationRepository;
use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Geo\GeoPoint;
use App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use PHPUnit\Framework\TestCase;

class ImportTripCalculationServiceTest extends TestCase
{
    private InMemoryTripCalculationRepository $tripCalculationRepository;

    private InMemoryTripCalculationLinkRepository $tripCalculationLinkRepository;

    private TripCalculationLink $tripCalculationLink;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->tripCalculationRepository = new InMemoryTripCalculationRepository();
        $this->tripCalculationLinkRepository = new InMemoryTripCalculationLinkRepository();

        $this->tripCalculationLink = TripCalculationLinkMother::forAccount('account-id');
        $this->tripCalculationLinkRepository->add($this->tripCalculationLink);
    }

    public function testImportsTheCalculationWithItsDateEmissionsAndAManualLeg(): void
    {
        $mobilityId = MobilityId::generate();
        $registeredAt = new \DateTimeImmutable('2024-08-29 10:15:30');

        $response = ($this->createSUT())(
            $this->request(
                origin: new TripWaypoint('place-madrid', 'Madrid, España', new GeoPoint(40.41, -3.70)),
                destination: new TripWaypoint('place-valencia', 'Valencia, España', new GeoPoint(39.47, -0.37)),
                mobilityId: $mobilityId,
                registeredAt: $registeredAt,
            ),
            UserMother::systemUser(),
        );

        $tripCalculation = $this->tripCalculationRepository->find($response->tripCalculationId);

        $this->assertNotNull($tripCalculation);
        $this->assertEquals($registeredAt, $tripCalculation->registeredAt());
        $this->assertSame(2024, $tripCalculation->calculationYear());
        $this->assertSame(1, $tripCalculation->trips());
        $this->assertSame('152.4', $tripCalculation->mobilityEmissions()->co2eq());
        $this->assertSame('152.4', $tripCalculation->emissions()->co2eq());
        $this->assertNull($tripCalculation->emissionFactorGroupId());
        $this->assertSame('Madrid, España', $tripCalculation->originLabel());
        $this->assertSame('Valencia, España', $tripCalculation->destinationLabel());

        $legs = $tripCalculation->legs();
        $this->assertCount(1, $legs);
        $this->assertSame(0, $legs[0]->position());
        $this->assertSame(LegKind::Intercity, $legs[0]->kind());
        $this->assertTrue($mobilityId->equals($legs[0]->mobilityId()));
        $this->assertSame(2, $legs[0]->vehicleOccupants());
        $this->assertSame(355.2, $legs[0]->distanceKm());
        $this->assertSame(DistanceSource::Manual, $legs[0]->distanceSource());
    }

    public function testImportsTheCalculationWithoutLegsWithoutOrigin(): void
    {
        $response = ($this->createSUT())(
            $this->request(
                origin: null,
                destination: null,
                mobilityId: MobilityId::generate(),
                mobilityEmissions: new Emissions(),
                overnightEmissions: new Emissions(co2eq: '23.1'),
            ),
            UserMother::systemUser(),
        );

        $tripCalculation = $this->tripCalculationRepository->find($response->tripCalculationId);

        $this->assertNotNull($tripCalculation);
        $this->assertSame([], $tripCalculation->legs());
        $this->assertSame('23.1', $tripCalculation->emissions()->co2eq());
    }

    public function testThrowsWhenTheLinkDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->createSUT())(
            $this->request(tripCalculationLinkId: TripCalculationLinkId::generate()),
            UserMother::systemUser(),
        );
    }

    public function testDeniesAUserWithoutPermission(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        ($this->createSUT())(
            $this->request(),
            UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-id')),
        );
    }

    private function request(
        ?TripCalculationLinkId $tripCalculationLinkId = null,
        ?TripWaypoint $origin = null,
        ?TripWaypoint $destination = null,
        ?MobilityId $mobilityId = null,
        ?Emissions $mobilityEmissions = null,
        ?Emissions $overnightEmissions = null,
        ?\DateTimeImmutable $registeredAt = null,
    ): ImportTripCalculationRequest {
        return new ImportTripCalculationRequest(
            tripCalculationLinkId: $tripCalculationLinkId ?? $this->tripCalculationLink->id(),
            origin: $origin,
            destination: $destination,
            roundTrip: true,
            nights: 2,
            people: 2,
            mobilityId: $mobilityId,
            vehicleOccupants: 2,
            distanceKm: 355.2,
            mobilityEmissions: $mobilityEmissions ?? new Emissions(co2eq: '152.4'),
            overnightEmissions: $overnightEmissions ?? new Emissions(),
            registeredAt: $registeredAt ?? new \DateTimeImmutable('2025-01-10 08:00:00'),
        );
    }

    private function createSUT(): ImportTripCalculationService
    {
        return new ImportTripCalculationService(
            $this->tripCalculationRepository,
            $this->tripCalculationLinkRepository,
            new TripCalculationLinkAuthorizer(
                new InMemoryPropertyRepository(),
                new PropertyAuthorizer(),
                new StubCurrentAccountIdProvider(null),
            ),
        );
    }
}
