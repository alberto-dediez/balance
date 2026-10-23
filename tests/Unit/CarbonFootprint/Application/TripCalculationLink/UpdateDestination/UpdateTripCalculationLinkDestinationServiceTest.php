<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\TripCalculationLink\UpdateDestination;

use App\CarbonFootprint\Application\Trip\PropertyLocator;
use App\CarbonFootprint\Application\Trip\TripDestinationCandidates;
use App\CarbonFootprint\Application\TripCalculationLink\TripCalculationLinkAuthorizer;
use App\CarbonFootprint\Application\TripCalculationLink\UpdateDestination\UpdateTripCalculationLinkDestinationRequest;
use App\CarbonFootprint\Application\TripCalculationLink\UpdateDestination\UpdateTripCalculationLinkDestinationService;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkDestinationUpdated;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripDestinationMode;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripDestinationPlaceKind;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryTripCalculationLinkRepository;
use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyInfo;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryCountryRepository;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Geo\GeoBounds;
use App\Shared\Domain\Model\Geo\GeoPoint;
use App\Shared\Infrastructure\Geo\IntlCountryRegionCode;
use App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkMother;
use App\Tests\Unit\Property\Domain\Model\Country\CountryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Security\Application\Account\ConsultantSupervisionPolicyMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Model\Geo\InMemoryPlaceFinder;
use PHPUnit\Framework\TestCase;

class UpdateTripCalculationLinkDestinationServiceTest extends TestCase
{
    private InMemoryTripCalculationLinkRepository $tripCalculationLinkRepository;

    private InMemoryCountryRepository $countryRepository;

    private InMemoryPropertyRepository $propertyRepository;

    private InMemoryPlaceFinder $placeFinder;

    private Account $account;

    private TripCalculationLink $link;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->tripCalculationLinkRepository = new InMemoryTripCalculationLinkRepository();
        $this->countryRepository = new InMemoryCountryRepository();
        $this->propertyRepository = new InMemoryPropertyRepository();
        $this->placeFinder = new InMemoryPlaceFinder();
        $this->account = AccountMother::fromName('Agencia');

        $this->link = TripCalculationLinkMother::forAccount((string) $this->account->id());
        $this->tripCalculationLinkRepository->add($this->link);
    }

    public function testUpdatesToFree(): void
    {
        $response = ($this->createSUT())($this->request(TripDestinationMode::Free, placeKind: TripDestinationPlaceKind::Any), $this->owner());

        $this->assertSame(TripDestinationMode::Free, $response->tripCalculationLink->destinationMode());
        $this->assertSame(TripDestinationPlaceKind::Any, $response->tripCalculationLink->destinationPlaceKind());

        $events = array_filter(
            $response->tripCalculationLink->recordedEvents(),
            static fn(object $event): bool => $event instanceof TripCalculationLinkDestinationUpdated,
        );
        $this->assertNotEmpty($events);
    }

    public function testUpdatesToCountries(): void
    {
        $spain = CountryMother::fromNameAndIsoAndId('España', 'ESP', (string) CountryId::generate());
        $france = CountryMother::fromNameAndIsoAndId('Francia', 'FR', (string) CountryId::generate());
        $this->countryRepository->add($spain);
        $this->countryRepository->add($france);

        $response = ($this->createSUT())($this->request(TripDestinationMode::Countries, countryIds: [$spain->id(), $france->id()]), $this->owner());

        $this->assertEquals([$spain->id(), $france->id()], $response->tripCalculationLink->destinationCountryIds());
    }

    public function testRejectsACountryWhoseIsoIsNotACountryCode(): void
    {
        $country = CountryMother::fromNameAndIsoAndId('Desconocido', 'XYZ', (string) CountryId::generate());
        $this->countryRepository->add($country);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El país elegido no tiene código ISO.');

        ($this->createSUT())($this->request(TripDestinationMode::Countries, countryIds: [$country->id()]), $this->owner());
    }

    public function testRejectsACountryWithoutIso(): void
    {
        $country = CountryMother::fromName('Sin ISO');
        $this->countryRepository->add($country);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El país elegido no tiene código ISO.');

        ($this->createSUT())($this->request(TripDestinationMode::Countries, countryIds: [$country->id()]), $this->owner());
    }

    public function testUpdatesToARegionResolvingItsNameAndViewport(): void
    {
        $bounds = new GeoBounds(new GeoPoint(37.84, -1.53), new GeoPoint(40.79, 0.69));
        $this->placeFinder->add('region-valenciana', 'Comunitat Valenciana, España', 39.48, -0.75, 'Comunitat Valenciana', $bounds);

        $response = ($this->createSUT())($this->request(TripDestinationMode::Region, regionReference: 'region-valenciana'), $this->owner());

        $link = $response->tripCalculationLink;
        $this->assertSame('region-valenciana', $link->destinationRegionReference());
        $this->assertSame('Comunitat Valenciana', $link->destinationRegionLabel());
        $this->assertEquals($bounds, $link->destinationRegionBounds());
    }

    public function testRejectsARegionWithoutViewport(): void
    {
        $this->placeFinder->add('region-sin-zona', 'Región', 39.48, -0.75, 'Región');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No se ha podido obtener la zona de la región elegida.');

        ($this->createSUT())($this->request(TripDestinationMode::Region, regionReference: 'region-sin-zona'), $this->owner());
    }

    public function testRejectsTheRegionModeWithoutAChosenRegion(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Debes elegir una región.');

        ($this->createSUT())($this->request(TripDestinationMode::Region, regionReference: ' '), $this->owner());
    }

    public function testUpdatesToAllProperties(): void
    {
        $response = ($this->createSUT())($this->request(TripDestinationMode::AllProperties), $this->owner());

        $this->assertSame(TripDestinationMode::AllProperties, $response->tripCalculationLink->destinationMode());
    }

    public function testUpdatesToSelectedCandidateProperties(): void
    {
        $property = PropertyMother::from(PropertyId::generate(), 'Hotel', $this->account->id(), new PropertyInfo(latitude: '39.4', longitude: '-0.3'));
        $this->propertyRepository->add($property);

        $response = ($this->createSUT())($this->request(TripDestinationMode::SelectedProperties, propertyIds: [$property->id()]), $this->owner());

        $this->assertEquals([$property->id()], $response->tripCalculationLink->destinationPropertyIds());
    }

    public function testRejectsAPropertyThatIsNotACandidate(): void
    {
        $foreign = PropertyMother::from(PropertyId::generate(), 'Hotel ajeno', AccountId::generate(), new PropertyInfo(latitude: '39.4', longitude: '-0.3'));
        $this->propertyRepository->add($foreign);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La propiedad elegida no está disponible para esta calculadora.');

        ($this->createSUT())($this->request(TripDestinationMode::SelectedProperties, propertyIds: [$foreign->id()]), $this->owner());
    }

    public function testDeniesAnOwnerOfAnotherAccount(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        ($this->createSUT())(
            $this->request(TripDestinationMode::Free),
            UserMother::fromUsernameAndRoleAndAccount('intruder', Role::Owner, AccountId::generate()),
        );
    }

    /**
     * @param list<CountryId>  $countryIds
     * @param list<PropertyId> $propertyIds
     */
    private function request(
        TripDestinationMode $mode,
        TripDestinationPlaceKind $placeKind = TripDestinationPlaceKind::City,
        array $countryIds = [],
        array $propertyIds = [],
        ?string $regionReference = null,
    ): UpdateTripCalculationLinkDestinationRequest {
        return new UpdateTripCalculationLinkDestinationRequest($this->link->id(), $mode, $placeKind, $countryIds, $propertyIds, $regionReference);
    }

    private function owner(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $this->account->id());
    }

    private function createSUT(): UpdateTripCalculationLinkDestinationService
    {
        return new UpdateTripCalculationLinkDestinationService(
            $this->tripCalculationLinkRepository,
            new TripCalculationLinkAuthorizer($this->propertyRepository, new PropertyAuthorizer()),
            $this->countryRepository,
            new TripDestinationCandidates($this->propertyRepository, ConsultantSupervisionPolicyMother::create([$this->account]), new PropertyLocator()),
            $this->placeFinder,
            new IntlCountryRegionCode(),
        );
    }
}
