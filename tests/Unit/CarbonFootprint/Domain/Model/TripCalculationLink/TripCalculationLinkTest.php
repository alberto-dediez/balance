<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculationLink;

use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkDestinationNotConfigurableException;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkDestinationUpdated;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkRegistered;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkScopeUpdated;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationScope;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripDestinationMode;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripDestinationPlaceKind;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Geo\GeoBounds;
use App\Shared\Domain\Model\Geo\GeoPoint;
use PHPUnit\Framework\TestCase;

class TripCalculationLinkTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testRegisterStoresPropertyAccountAndToken(): void
    {
        $propertyId = PropertyId::generate();
        $accountId = AccountId::generate();

        $tripCalculationLink = new TripCalculationLink(TripCalculationLinkId::generate());
        $tripCalculationLink->register(
            propertyId: $propertyId,
            accountId: $accountId,
            token: TripCalculationLink::generateToken(),
        );

        $storedPropertyId = $tripCalculationLink->propertyId();
        $storedAccountId = $tripCalculationLink->accountId();

        $this->assertNotNull($storedPropertyId);
        $this->assertNotNull($storedAccountId);
        $this->assertTrue($propertyId->equals($storedPropertyId));
        $this->assertTrue($accountId->equals($storedAccountId));
        $this->assertNotEmpty($tripCalculationLink->token());

        $events = array_filter(
            $tripCalculationLink->recordedEvents(),
            static fn($event) => $event instanceof TripCalculationLinkRegistered,
        );
        $this->assertNotEmpty($events);
    }

    public function testCollectsTransportAndOvernightStayByDefault(): void
    {
        $tripCalculationLink = TripCalculationLinkMother::registeredTripCalculationLink();

        $this->assertSame(TripCalculationScope::Both, $tripCalculationLink->scope());
        $this->assertTrue($tripCalculationLink->scope()->asksForBoth());
        $this->assertTrue($tripCalculationLink->scope()->includesTransport());
        $this->assertTrue($tripCalculationLink->scope()->includesOvernightStay());
    }

    public function testUpdateScopeStoresTheNewScope(): void
    {
        $tripCalculationLink = TripCalculationLinkMother::registeredTripCalculationLink();

        $tripCalculationLink->updateScope(TripCalculationScope::OvernightStay);

        $this->assertSame(TripCalculationScope::OvernightStay, $tripCalculationLink->scope());
        $this->assertFalse($tripCalculationLink->scope()->includesTransport());
        $this->assertFalse($tripCalculationLink->scope()->asksForBoth());

        $events = array_filter(
            $tripCalculationLink->recordedEvents(),
            static fn($event) => $event instanceof TripCalculationLinkScopeUpdated,
        );
        $this->assertNotEmpty($events);
    }

    public function testGeneratesADifferentTokenEveryTime(): void
    {
        $this->assertNotSame(TripCalculationLink::generateToken(), TripCalculationLink::generateToken());
    }

    public function testRegisterForAccountLeavesThePropertyEmpty(): void
    {
        $accountId = AccountId::generate();

        $tripCalculationLink = new TripCalculationLink(TripCalculationLinkId::generate());
        $tripCalculationLink->registerForAccount(
            accountId: $accountId,
            token: TripCalculationLink::generateToken(),
        );

        $storedAccountId = $tripCalculationLink->accountId();

        $this->assertNull($tripCalculationLink->propertyId());
        $this->assertFalse($tripCalculationLink->isForProperty());
        $this->assertNotNull($storedAccountId);
        $this->assertTrue($accountId->equals($storedAccountId));
        $this->assertNotEmpty($tripCalculationLink->token());
        $this->assertSame(TripDestinationMode::Free, $tripCalculationLink->destinationMode());
        $this->assertSame(TripDestinationPlaceKind::City, $tripCalculationLink->destinationPlaceKind());

        $events = $this->eventsOf($tripCalculationLink, TripCalculationLinkRegistered::class);
        $this->assertCount(1, $events);
        $this->assertNull($events[0]->payload()['propertyId']);
    }

    public function testRegisterForPropertyIsForProperty(): void
    {
        $this->assertTrue(TripCalculationLinkMother::registeredTripCalculationLink()->isForProperty());
    }

    public function testUpdateDestinationToCountriesStoresThemAndClearsTheOtherModes(): void
    {
        $tripCalculationLink = TripCalculationLinkMother::forAccountWithDestination(
            (string) AccountId::generate(),
            TripDestinationMode::SelectedProperties,
            propertyIds: [PropertyId::generate()],
        );
        $spain = CountryId::generate();
        $france = CountryId::generate();

        $tripCalculationLink->updateDestination(
            TripDestinationMode::Countries,
            TripDestinationPlaceKind::Lodging,
            [$spain, $france, $spain],
            [PropertyId::generate()],
            'region-id',
            'Región',
            $this->bounds(),
        );

        $this->assertSame(TripDestinationMode::Countries, $tripCalculationLink->destinationMode());
        $this->assertSame(TripDestinationPlaceKind::Lodging, $tripCalculationLink->destinationPlaceKind());
        $this->assertEquals([$spain, $france], $tripCalculationLink->destinationCountryIds());
        $this->assertSame([], $tripCalculationLink->destinationPropertyIds());
        $this->assertNull($tripCalculationLink->destinationRegionReference());
        $this->assertNull($tripCalculationLink->destinationRegionLabel());
        $this->assertNull($tripCalculationLink->destinationRegionBounds());

        $events = $this->eventsOf($tripCalculationLink, TripCalculationLinkDestinationUpdated::class);
        $payload = $events[count($events) - 1]->payload();
        $this->assertSame('countries', $payload['mode']);
        $this->assertSame('lodging', $payload['placeKind']);
        $this->assertSame([(string) $spain, (string) $france], $payload['countryIds']);
        $this->assertSame([], $payload['propertyIds']);
        $this->assertNull($payload['regionReference']);
        $this->assertNull($payload['regionLowLatitude']);
    }

    public function testUpdateDestinationToRegionStoresItAndClearsTheOtherModes(): void
    {
        $tripCalculationLink = TripCalculationLinkMother::forAccountWithDestination(
            (string) AccountId::generate(),
            TripDestinationMode::Countries,
            countryIds: [CountryId::generate()],
        );

        $tripCalculationLink->updateDestination(
            TripDestinationMode::Region,
            TripDestinationPlaceKind::City,
            [CountryId::generate()],
            [],
            'region-id',
            'Comunitat Valenciana',
            $this->bounds(),
        );

        $bounds = $tripCalculationLink->destinationRegionBounds();

        $this->assertSame(TripDestinationMode::Region, $tripCalculationLink->destinationMode());
        $this->assertSame([], $tripCalculationLink->destinationCountryIds());
        $this->assertSame('region-id', $tripCalculationLink->destinationRegionReference());
        $this->assertSame('Comunitat Valenciana', $tripCalculationLink->destinationRegionLabel());
        $this->assertNotNull($bounds);
        $this->assertSame(37.8, $bounds->low->latitude);
        $this->assertSame(-1.5, $bounds->low->longitude);
        $this->assertSame(40.8, $bounds->high->latitude);
        $this->assertSame(0.7, $bounds->high->longitude);

        $events = $this->eventsOf($tripCalculationLink, TripCalculationLinkDestinationUpdated::class);
        $payload = $events[count($events) - 1]->payload();
        $this->assertSame('region', $payload['mode']);
        $this->assertSame([], $payload['countryIds']);
        $this->assertSame('region-id', $payload['regionReference']);
        $this->assertSame('Comunitat Valenciana', $payload['regionLabel']);
        $this->assertSame(37.8, $payload['regionLowLatitude']);
        $this->assertSame(-1.5, $payload['regionLowLongitude']);
        $this->assertSame(40.8, $payload['regionHighLatitude']);
        $this->assertSame(0.7, $payload['regionHighLongitude']);
    }

    public function testUpdateDestinationToSelectedPropertiesStoresThem(): void
    {
        $tripCalculationLink = TripCalculationLinkMother::forAccount((string) AccountId::generate());
        $propertyId = PropertyId::generate();

        $tripCalculationLink->updateDestination(TripDestinationMode::SelectedProperties, TripDestinationPlaceKind::City, [CountryId::generate()], [$propertyId, $propertyId], null, null, null);

        $this->assertSame(TripDestinationMode::SelectedProperties, $tripCalculationLink->destinationMode());
        $this->assertEquals([$propertyId], $tripCalculationLink->destinationPropertyIds());
        $this->assertSame([], $tripCalculationLink->destinationCountryIds());

        $events = $this->eventsOf($tripCalculationLink, TripCalculationLinkDestinationUpdated::class);
        $this->assertSame([(string) $propertyId], $events[count($events) - 1]->payload()['propertyIds']);
    }

    public function testUpdateDestinationToFreeOrAllPropertiesClearsEverything(): void
    {
        foreach ([TripDestinationMode::Free, TripDestinationMode::AllProperties] as $mode) {
            $tripCalculationLink = TripCalculationLinkMother::forAccountWithDestination(
                (string) AccountId::generate(),
                TripDestinationMode::Region,
                regionReference: 'region-id',
                regionLabel: 'Región',
                regionBounds: $this->bounds(),
            );

            $tripCalculationLink->updateDestination($mode, TripDestinationPlaceKind::Any, [CountryId::generate()], [PropertyId::generate()], 'region-id', 'Región', $this->bounds());

            $this->assertSame($mode, $tripCalculationLink->destinationMode());
            $this->assertSame([], $tripCalculationLink->destinationCountryIds());
            $this->assertSame([], $tripCalculationLink->destinationPropertyIds());
            $this->assertNull($tripCalculationLink->destinationRegionReference());
            $this->assertNull($tripCalculationLink->destinationRegionBounds());
        }
    }

    public function testUpdateDestinationRequiresAtLeastOneCountry(): void
    {
        $tripCalculationLink = TripCalculationLinkMother::forAccount((string) AccountId::generate());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Debes elegir entre 1 y 15 países.');

        $tripCalculationLink->updateDestination(TripDestinationMode::Countries, TripDestinationPlaceKind::City, [], [], null, null, null);
    }

    public function testUpdateDestinationAdmitsUpToFifteenCountries(): void
    {
        $tripCalculationLink = TripCalculationLinkMother::forAccount((string) AccountId::generate());
        $countryIds = array_map(static fn(int $i): CountryId => CountryId::generate(), range(1, 15));

        $tripCalculationLink->updateDestination(TripDestinationMode::Countries, TripDestinationPlaceKind::City, $countryIds, [], null, null, null);

        $this->assertEquals($countryIds, $tripCalculationLink->destinationCountryIds());
    }

    public function testUpdateDestinationRejectsMoreThanFifteenCountries(): void
    {
        $tripCalculationLink = TripCalculationLinkMother::forAccount((string) AccountId::generate());
        $countryIds = array_map(static fn(int $i): CountryId => CountryId::generate(), range(1, 16));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Debes elegir entre 1 y 15 países.');

        $tripCalculationLink->updateDestination(TripDestinationMode::Countries, TripDestinationPlaceKind::City, $countryIds, [], null, null, null);
    }

    public function testUpdateDestinationRequiresACompleteRegion(): void
    {
        $tripCalculationLink = TripCalculationLinkMother::forAccount((string) AccountId::generate());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Debes elegir una región.');

        $tripCalculationLink->updateDestination(TripDestinationMode::Region, TripDestinationPlaceKind::City, [], [], 'region-id', 'Región', null);
    }

    public function testUpdateDestinationRequiresAtLeastOneSelectedProperty(): void
    {
        $tripCalculationLink = TripCalculationLinkMother::forAccount((string) AccountId::generate());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Debes elegir al menos una propiedad.');

        $tripCalculationLink->updateDestination(TripDestinationMode::SelectedProperties, TripDestinationPlaceKind::City, [], [], null, null, null);
    }

    public function testPropertyLinksDoNotAdmitADestination(): void
    {
        $tripCalculationLink = TripCalculationLinkMother::registeredTripCalculationLink();

        $this->expectException(TripCalculationLinkDestinationNotConfigurableException::class);

        $tripCalculationLink->updateDestination(TripDestinationMode::Free, TripDestinationPlaceKind::City, [], [], null, null, null);
    }

    public function testRebuildsTheRegisteredEventWithoutProperty(): void
    {
        $event = new TripCalculationLinkRegistered(TripCalculationLinkId::generate(), null, AccountId::generate(), 'token');

        $rebuilt = TripCalculationLinkRegistered::rebuildFromPayload($event->id(), $event->occurredOn(), $event->payload());

        $this->assertNull($rebuilt->propertyId());
        $this->assertSame($event->payload(), $rebuilt->payload());
    }

    public function testRebuildsTheRegisteredEventOfAPropertyLink(): void
    {
        $propertyId = PropertyId::generate();
        $event = new TripCalculationLinkRegistered(TripCalculationLinkId::generate(), $propertyId, AccountId::generate(), 'token');

        $rebuilt = TripCalculationLinkRegistered::rebuildFromPayload($event->id(), $event->occurredOn(), $event->payload());

        $rebuiltPropertyId = $rebuilt->propertyId();
        $this->assertNotNull($rebuiltPropertyId);
        $this->assertTrue($propertyId->equals($rebuiltPropertyId));
    }

    public function testRebuildsTheDestinationUpdatedEvent(): void
    {
        $event = new TripCalculationLinkDestinationUpdated(
            TripCalculationLinkId::generate(),
            TripDestinationMode::Region,
            TripDestinationPlaceKind::Lodging,
            [CountryId::generate()],
            [PropertyId::generate()],
            'region-id',
            'Comunitat Valenciana',
            $this->bounds(),
        );

        $rebuilt = TripCalculationLinkDestinationUpdated::rebuildFromPayload($event->id(), $event->occurredOn(), $event->payload());

        $this->assertSame($event->payload(), $rebuilt->payload());
        $this->assertEquals($this->bounds(), $rebuilt->regionBounds());
    }

    private function bounds(): GeoBounds
    {
        return new GeoBounds(new GeoPoint(37.8, -1.5), new GeoPoint(40.8, 0.7));
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return list<T>
     */
    private function eventsOf(TripCalculationLink $tripCalculationLink, string $class): array
    {
        return array_values(array_filter(
            $tripCalculationLink->recordedEvents(),
            static fn(object $event): bool => $event instanceof $class,
        ));
    }
}
