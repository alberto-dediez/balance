<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\TripCalculationLink\SuggestDestinations;

use App\CarbonFootprint\Application\Trip\PropertyLocator;
use App\CarbonFootprint\Application\Trip\TripDestinationCandidates;
use App\CarbonFootprint\Application\Trip\TripDestinationSearchArea;
use App\CarbonFootprint\Application\TripCalculationLink\SuggestDestinations\SuggestTripDestinationsRequest;
use App\CarbonFootprint\Application\TripCalculationLink\SuggestDestinations\SuggestTripDestinationsService;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkTokenInvalidException;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripDestinationMode;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryTripCalculationLinkRepository;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyInfo;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryCountryRepository;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Geo\GeoBounds;
use App\Shared\Domain\Model\Geo\GeoPoint;
use App\Shared\Domain\Model\Geo\PlaceSearchArea;
use App\Shared\Domain\Model\Geo\PlaceSuggestion;
use App\Shared\Infrastructure\Geo\IntlCountryRegionCode;
use App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkMother;
use App\Tests\Unit\Property\Domain\Model\Country\CountryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Security\Application\Account\ConsultantSupervisionPolicyMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Shared\Domain\Model\Geo\InMemoryPlaceFinder;
use PHPUnit\Framework\TestCase;

class SuggestTripDestinationsServiceTest extends TestCase
{
    private InMemoryTripCalculationLinkRepository $tripCalculationLinkRepository;

    private InMemoryPropertyRepository $propertyRepository;

    private InMemoryCountryRepository $countryRepository;

    private InMemoryPlaceFinder $placeFinder;

    private Account $account;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->tripCalculationLinkRepository = new InMemoryTripCalculationLinkRepository();
        $this->propertyRepository = new InMemoryPropertyRepository();
        $this->countryRepository = new InMemoryCountryRepository();
        $this->placeFinder = new InMemoryPlaceFinder();
        $this->account = AccountMother::fromName('Agencia');

        $this->placeFinder->add('place-valencia', 'Valencia, España', 39.4699, -0.3763);
    }

    public function testRejectsAnInvalidToken(): void
    {
        $this->expectException(TripCalculationLinkTokenInvalidException::class);

        ($this->createSUT())(new SuggestTripDestinationsRequest('invalid-token', 'Valencia'));
    }

    public function testRejectsTheTokenOfAPropertyLink(): void
    {
        $link = TripCalculationLinkMother::registeredTripCalculationLink();
        $this->tripCalculationLinkRepository->add($link);

        $this->expectException(TripCalculationLinkTokenInvalidException::class);

        ($this->createSUT())(new SuggestTripDestinationsRequest($link->token(), 'Valencia'));
    }

    public function testReturnsNothingForAShortQuery(): void
    {
        $link = TripCalculationLinkMother::forAccount((string) $this->account->id());
        $this->tripCalculationLinkRepository->add($link);

        $response = ($this->createSUT())(new SuggestTripDestinationsRequest($link->token(), ' Va '));

        $this->assertSame([], $response->suggestions);
        $this->assertSame(0, $this->placeFinder->suggestCalls());
    }

    public function testCountriesModeDoesNotQueryThePlaceFinderForAnEmptyOrShortQuery(): void
    {
        $spain = CountryMother::fromNameAndIsoAndId('España', 'ESP', (string) CountryId::generate());
        $this->countryRepository->add($spain);
        $link = TripCalculationLinkMother::forAccountWithDestination((string) $this->account->id(), TripDestinationMode::Countries, countryIds: [$spain->id()]);
        $this->tripCalculationLinkRepository->add($link);

        $emptyResponse = ($this->createSUT())(new SuggestTripDestinationsRequest($link->token(), ''));
        $shortResponse = ($this->createSUT())(new SuggestTripDestinationsRequest($link->token(), ' Va '));

        $this->assertSame([], $emptyResponse->suggestions);
        $this->assertSame([], $shortResponse->suggestions);
        $this->assertSame(0, $this->placeFinder->suggestCalls());
    }

    public function testRegionModeDoesNotQueryThePlaceFinderForAnEmptyOrShortQuery(): void
    {
        $link = TripCalculationLinkMother::forAccountWithDestination(
            (string) $this->account->id(),
            TripDestinationMode::Region,
            regionReference: 'region-valenciana',
            regionLabel: 'Comunitat Valenciana',
            regionBounds: new GeoBounds(new GeoPoint(37.84, -1.53), new GeoPoint(40.79, 0.69)),
        );
        $this->tripCalculationLinkRepository->add($link);

        $emptyResponse = ($this->createSUT())(new SuggestTripDestinationsRequest($link->token(), ''));
        $shortResponse = ($this->createSUT())(new SuggestTripDestinationsRequest($link->token(), ' Va '));

        $this->assertSame([], $emptyResponse->suggestions);
        $this->assertSame([], $shortResponse->suggestions);
        $this->assertSame(0, $this->placeFinder->suggestCalls());
    }

    public function testCountriesModePassesTheSearchAreaToThePlaceFinder(): void
    {
        $spain = CountryMother::fromNameAndIsoAndId('España', 'ESP', (string) CountryId::generate());
        $this->countryRepository->add($spain);
        $link = TripCalculationLinkMother::forAccountWithDestination((string) $this->account->id(), TripDestinationMode::Countries, countryIds: [$spain->id()]);
        $this->tripCalculationLinkRepository->add($link);

        $response = ($this->createSUT())(new SuggestTripDestinationsRequest($link->token(), 'Valencia'));

        $this->assertCount(1, $response->suggestions);
        $this->assertSame('place-valencia', $response->suggestions[0]->reference);
        $this->assertEquals(new PlaceSearchArea(regionCodes: ['es']), $this->placeFinder->lastArea());
    }

    public function testCountriesModeSendsAlpha2IsoCodesAsTheyAre(): void
    {
        $france = CountryMother::fromNameAndIsoAndId('Francia', 'FR', (string) CountryId::generate());
        $this->countryRepository->add($france);
        $link = TripCalculationLinkMother::forAccountWithDestination((string) $this->account->id(), TripDestinationMode::Countries, countryIds: [$france->id()]);
        $this->tripCalculationLinkRepository->add($link);

        ($this->createSUT())(new SuggestTripDestinationsRequest($link->token(), 'Valencia'));

        $this->assertEquals(new PlaceSearchArea(regionCodes: ['fr']), $this->placeFinder->lastArea());
    }

    public function testFreeModeDoesNotRestrictTheSearch(): void
    {
        $link = TripCalculationLinkMother::forAccount((string) $this->account->id());
        $this->tripCalculationLinkRepository->add($link);

        $response = ($this->createSUT())(new SuggestTripDestinationsRequest($link->token(), 'Valencia'));

        $this->assertCount(1, $response->suggestions);
        $this->assertNull($this->placeFinder->lastArea());
    }

    public function testAllPropertiesModeSuggestsTheMatchingProperties(): void
    {
        $hotel = PropertyMother::from(PropertyId::generate(), 'Hotel Mar', $this->account->id(), new PropertyInfo(latitude: '39.4', longitude: '-0.3'));
        $this->propertyRepository->add($hotel);
        $this->propertyRepository->add(PropertyMother::from(PropertyId::generate(), 'Apartamentos Sol', $this->account->id(), new PropertyInfo(latitude: '39.4', longitude: '-0.3')));
        $this->propertyRepository->add(PropertyMother::from(PropertyId::generate(), 'Hotel ajeno', AccountId::generate(), new PropertyInfo(latitude: '39.4', longitude: '-0.3')));
        $link = TripCalculationLinkMother::forAccountWithDestination((string) $this->account->id(), TripDestinationMode::AllProperties);
        $this->tripCalculationLinkRepository->add($link);

        $response = ($this->createSUT())(new SuggestTripDestinationsRequest($link->token(), 'hotel'));

        $this->assertCount(1, $response->suggestions);
        $this->assertSame('property:' . $hotel->id(), $response->suggestions[0]->reference);
        $this->assertSame('Hotel Mar', $response->suggestions[0]->label);
    }

    public function testAllPropertiesModeSuggestsEveryAllowedPropertyForAnEmptyQuery(): void
    {
        $hotel = $this->addLocatedProperty('Hotel Mar');
        $apartments = $this->addLocatedProperty('Apartamentos Sol');
        $ruralHouse = $this->addLocatedProperty('Casa Rural Luz');
        $this->propertyRepository->add(PropertyMother::from(PropertyId::generate(), 'Hotel ajeno', AccountId::generate(), new PropertyInfo(latitude: '39.4', longitude: '-0.3')));
        $link = TripCalculationLinkMother::forAccountWithDestination((string) $this->account->id(), TripDestinationMode::AllProperties);
        $this->tripCalculationLinkRepository->add($link);

        $response = ($this->createSUT())(new SuggestTripDestinationsRequest($link->token(), ''));

        $this->assertSame(
            ['property:' . $apartments->id(), 'property:' . $ruralHouse->id(), 'property:' . $hotel->id()],
            array_map(static fn(PlaceSuggestion $suggestion): string => $suggestion->reference, $response->suggestions),
        );
        $this->assertSame(
            ['Apartamentos Sol', 'Casa Rural Luz', 'Hotel Mar'],
            array_map(static fn(PlaceSuggestion $suggestion): string => $suggestion->label, $response->suggestions),
        );
    }

    public function testSelectedPropertiesModeSuggestsOnlyTheSelectedOnesForAnEmptyQuery(): void
    {
        $hotel = $this->addLocatedProperty('Hotel Mar');
        $this->addLocatedProperty('Apartamentos Sol');
        $link = TripCalculationLinkMother::forAccountWithDestination((string) $this->account->id(), TripDestinationMode::SelectedProperties, propertyIds: [$hotel->id()]);
        $this->tripCalculationLinkRepository->add($link);

        $response = ($this->createSUT())(new SuggestTripDestinationsRequest($link->token(), ''));

        $this->assertCount(1, $response->suggestions);
        $this->assertSame('property:' . $hotel->id(), $response->suggestions[0]->reference);
    }

    public function testPropertiesModeIgnoresAPropertyWithoutCoordinatesForAnEmptyQuery(): void
    {
        $hotel = $this->addLocatedProperty('Hotel Mar');
        $this->propertyRepository->add(PropertyMother::from(PropertyId::generate(), 'Hotel sin ubicar', $this->account->id(), new PropertyInfo()));
        $link = TripCalculationLinkMother::forAccountWithDestination((string) $this->account->id(), TripDestinationMode::AllProperties);
        $this->tripCalculationLinkRepository->add($link);

        $response = ($this->createSUT())(new SuggestTripDestinationsRequest($link->token(), ''));

        $this->assertCount(1, $response->suggestions);
        $this->assertSame('property:' . $hotel->id(), $response->suggestions[0]->reference);
    }

    public function testPropertiesModeFiltersByNameFromTheFirstLetter(): void
    {
        $this->addLocatedProperty('Hotel Mar');
        $this->addLocatedProperty('Apartamentos Sol');
        $this->addLocatedProperty('Casa Rural Luz');
        $link = TripCalculationLinkMother::forAccountWithDestination((string) $this->account->id(), TripDestinationMode::AllProperties);
        $this->tripCalculationLinkRepository->add($link);

        $response = ($this->createSUT())(new SuggestTripDestinationsRequest($link->token(), 'm'));

        $this->assertSame(
            ['Apartamentos Sol', 'Hotel Mar'],
            array_map(static fn(PlaceSuggestion $suggestion): string => $suggestion->label, $response->suggestions),
        );
    }

    public function testPropertiesModeReturnsAtMostFiftyProperties(): void
    {
        for ($number = 1; $number <= 51; ++$number) {
            $this->addLocatedProperty(sprintf('Hotel %02d', $number));
        }
        $link = TripCalculationLinkMother::forAccountWithDestination((string) $this->account->id(), TripDestinationMode::AllProperties);
        $this->tripCalculationLinkRepository->add($link);

        $response = ($this->createSUT())(new SuggestTripDestinationsRequest($link->token(), ''));

        $labels = array_map(static fn(PlaceSuggestion $suggestion): string => $suggestion->label, $response->suggestions);
        $this->assertCount(50, $labels);
        $this->assertSame('Hotel 01', $labels[0]);
        $this->assertSame('Hotel 50', $labels[49]);
        $this->assertNotContains('Hotel 51', $labels);
    }

    public function testFreeModeReturnsNothingForAnEmptyQuery(): void
    {
        $link = TripCalculationLinkMother::forAccount((string) $this->account->id());
        $this->tripCalculationLinkRepository->add($link);

        $response = ($this->createSUT())(new SuggestTripDestinationsRequest($link->token(), ''));

        $this->assertSame([], $response->suggestions);
        $this->assertSame(0, $this->placeFinder->suggestCalls());
    }

    private function addLocatedProperty(string $name): Property
    {
        $property = PropertyMother::from(PropertyId::generate(), $name, $this->account->id(), new PropertyInfo(latitude: '39.4', longitude: '-0.3'));
        $this->propertyRepository->add($property);

        return $property;
    }

    private function createSUT(): SuggestTripDestinationsService
    {
        return new SuggestTripDestinationsService(
            $this->tripCalculationLinkRepository,
            $this->placeFinder,
            new TripDestinationCandidates($this->propertyRepository, ConsultantSupervisionPolicyMother::create([$this->account]), new PropertyLocator()),
            new TripDestinationSearchArea($this->countryRepository, new IntlCountryRegionCode()),
        );
    }
}
