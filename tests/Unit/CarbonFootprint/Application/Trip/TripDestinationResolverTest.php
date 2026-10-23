<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Trip;

use App\CarbonFootprint\Application\Trip\PropertyLocator;
use App\CarbonFootprint\Application\Trip\TripDestinationCandidates;
use App\CarbonFootprint\Application\Trip\TripDestinationResolver;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripDestinationMode;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripDestinationNotAllowedException;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyInfo;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryCountryRepository;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Domain\Model\Account\Account;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Geo\GeoBounds;
use App\Shared\Domain\Model\Geo\GeoPoint;
use App\Shared\Infrastructure\Geo\IntlCountryRegionCode;
use App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkMother;
use App\Tests\Unit\Property\Domain\Model\Country\CountryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Security\Application\Account\ConsultantSupervisionPolicyMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Shared\Domain\Model\Geo\InMemoryPlaceFinder;
use PHPUnit\Framework\TestCase;

class TripDestinationResolverTest extends TestCase
{
    private InMemoryPlaceFinder $placeFinder;

    private InMemoryPropertyRepository $propertyRepository;

    private InMemoryCountryRepository $countryRepository;

    private Account $account;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->placeFinder = new InMemoryPlaceFinder();
        $this->propertyRepository = new InMemoryPropertyRepository();
        $this->countryRepository = new InMemoryCountryRepository();
        $this->account = AccountMother::fromName('Agencia');

        $this->placeFinder->add('place-valencia', 'Valencia, España', 39.4699, -0.3763, 'Valencia', null, ['Valencia', 'Comunitat Valenciana', 'España'], 'ES');
        $this->placeFinder->add('place-paris', 'París, Francia', 48.8566, 2.3522, 'París', null, ['París', 'Île-de-France', 'Francia'], 'FR');
        $this->placeFinder->add('place-teruel', 'Teruel, España', 40.3456, -1.1065, 'Teruel', null, ['Teruel', 'Aragón', 'España'], 'ES');
        $this->placeFinder->add('place-madrid', 'Madrid, España', 40.4168, -3.7038, 'Madrid', null, ['Madrid', 'Comunidad de Madrid', 'España'], 'ES');
    }

    public function testAnEmptyReferenceIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Debes indicar el destino de tu viaje.');

        $this->createSUT()->resolve($this->link(TripDestinationMode::Free), '  ');
    }

    public function testFreeModeAcceptsAnyResolvedPlace(): void
    {
        $waypoint = $this->createSUT()->resolve($this->link(TripDestinationMode::Free), 'place-paris');

        $this->assertSame('place-paris', $waypoint->reference);
        $this->assertSame('París, Francia', $waypoint->label);
        $this->assertEquals(new GeoPoint(48.8566, 2.3522), $waypoint->point);
    }

    public function testFreeModeKeepsTheReferenceOfAnUnresolvablePlace(): void
    {
        $waypoint = $this->createSUT()->resolve($this->link(TripDestinationMode::Free), 'unknown-place');

        $this->assertSame('unknown-place', $waypoint->reference);
        $this->assertNull($waypoint->label);
        $this->assertNull($waypoint->point);
    }

    public function testCountriesModeAcceptsAPlaceInAChosenCountry(): void
    {
        $waypoint = $this->createSUT()->resolve($this->countriesLink(), 'place-valencia');

        $this->assertSame('Valencia, España', $waypoint->label);
    }

    public function testCountriesModeAcceptsACountryWithAnAlpha2IsoCode(): void
    {
        $waypoint = $this->createSUT()->resolve($this->countriesLink('ES'), 'place-valencia');

        $this->assertSame('Valencia, España', $waypoint->label);
    }

    public function testCountriesModeRejectsAPlaceInAnotherCountry(): void
    {
        $this->expectException(TripDestinationNotAllowedException::class);

        $this->createSUT()->resolve($this->countriesLink(), 'place-paris');
    }

    public function testCountriesModeRejectsAnUnresolvablePlace(): void
    {
        $this->expectException(TripDestinationNotAllowedException::class);

        $this->createSUT()->resolve($this->countriesLink(), 'unknown-place');
    }

    public function testCountriesModeRejectsAPlaceWithoutCountry(): void
    {
        $this->placeFinder->add('place-sin-pais', 'Alta mar', 39.0, 0.5, 'Alta mar');

        $this->expectException(TripDestinationNotAllowedException::class);

        $this->createSUT()->resolve($this->countriesLink(), 'place-sin-pais');
    }

    public function testRegionModeRejectsAnUnresolvablePlace(): void
    {
        $this->addValencianRegion();

        $this->expectException(TripDestinationNotAllowedException::class);

        $this->createSUT()->resolve($this->regionLink(), 'unknown-place');
    }

    public function testRegionModeAcceptsAPlaceInsideTheRegion(): void
    {
        $this->addValencianRegion();

        $waypoint = $this->createSUT()->resolve($this->regionLink(), 'place-valencia');

        $this->assertSame('place-valencia', $waypoint->reference);
    }

    public function testRegionModeRejectsAPlaceOutsideTheRectangle(): void
    {
        $this->addValencianRegion();

        $this->expectException(TripDestinationNotAllowedException::class);

        $this->createSUT()->resolve($this->regionLink(), 'place-madrid');
    }

    public function testRegionModeRejectsAPlaceInsideTheRectangleOfAnotherRegion(): void
    {
        $this->addValencianRegion();

        $this->expectException(TripDestinationNotAllowedException::class);

        $this->createSUT()->resolve($this->regionLink(), 'place-teruel');
    }

    public function testRegionModeAcceptsAPlaceInsideARegionOfAMinorAreaType(): void
    {
        $bounds = new GeoBounds(new GeoPoint(39.45, -0.39), new GeoPoint(39.47, -0.36));
        $this->placeFinder->add('region-ruzafa', 'Ruzafa, Valencia, España', 39.46, -0.37, 'Ruzafa', $bounds, ['Ruzafa', 'Valencia', 'España'], 'ES');
        $this->placeFinder->add('place-ruzafa-hotel', 'Calle Sueca, Valencia, España', 39.462, -0.374, 'Calle Sueca', null, ['Ruzafa', 'Valencia', 'Comunitat Valenciana', 'España'], 'ES');
        $link = TripCalculationLinkMother::forAccountWithDestination(
            (string) $this->account->id(),
            TripDestinationMode::Region,
            regionReference: 'region-ruzafa',
            regionLabel: 'Ruzafa',
            regionBounds: $bounds,
        );

        $waypoint = $this->createSUT()->resolve($link, 'place-ruzafa-hotel');

        $this->assertSame('place-ruzafa-hotel', $waypoint->reference);
    }

    public function testRegionModeOnlyChecksTheRectangleWhenTheRegionCannotBeResolved(): void
    {
        $waypoint = $this->createSUT()->resolve($this->regionLink(), 'place-teruel');

        $this->assertSame('place-teruel', $waypoint->reference);
    }

    public function testPropertiesModeAcceptsAnAllowedProperty(): void
    {
        $property = $this->locatedProperty('Hotel Mar');

        $waypoint = $this->createSUT()->resolve($this->link(TripDestinationMode::AllProperties), 'property:' . $property->id());

        $this->assertSame('property:' . $property->id(), $waypoint->reference);
        $this->assertSame('Hotel Mar', $waypoint->label);
        $this->assertEquals(new GeoPoint(39.4699, -0.3763), $waypoint->point);
    }

    public function testPropertiesModeRejectsAPropertyOfAnotherAccount(): void
    {
        $foreign = PropertyMother::from(PropertyId::generate(), 'Hotel ajeno', AccountMother::random()->id(), new PropertyInfo(latitude: '39.4', longitude: '-0.3'));
        $this->propertyRepository->add($foreign);

        $this->expectException(TripDestinationNotAllowedException::class);

        $this->createSUT()->resolve($this->link(TripDestinationMode::AllProperties), 'property:' . $foreign->id());
    }

    public function testSelectedPropertiesModeOnlyAcceptsTheSelectedOnes(): void
    {
        $selected = $this->locatedProperty('Hotel A');
        $other = $this->locatedProperty('Hotel B');
        $link = TripCalculationLinkMother::forAccountWithDestination(
            (string) $this->account->id(),
            TripDestinationMode::SelectedProperties,
            propertyIds: [$selected->id()],
        );

        $this->assertSame('Hotel A', $this->createSUT()->resolve($link, 'property:' . $selected->id())->label);

        $this->expectException(TripDestinationNotAllowedException::class);

        $this->createSUT()->resolve($link, 'property:' . $other->id());
    }

    public function testPropertiesModeRejectsAGooglePlace(): void
    {
        $this->expectException(TripDestinationNotAllowedException::class);

        $this->createSUT()->resolve($this->link(TripDestinationMode::AllProperties), 'place-valencia');
    }

    private function link(TripDestinationMode $mode): TripCalculationLink
    {
        return TripCalculationLinkMother::forAccountWithDestination((string) $this->account->id(), $mode);
    }

    private function countriesLink(string $iso = 'ESP'): TripCalculationLink
    {
        $spain = CountryMother::fromNameAndIsoAndId('España', $iso, (string) CountryId::generate());
        $this->countryRepository->add($spain);

        return TripCalculationLinkMother::forAccountWithDestination(
            (string) $this->account->id(),
            TripDestinationMode::Countries,
            countryIds: [$spain->id()],
        );
    }

    private function regionLink(): TripCalculationLink
    {
        return TripCalculationLinkMother::forAccountWithDestination(
            (string) $this->account->id(),
            TripDestinationMode::Region,
            regionReference: 'region-valenciana',
            regionLabel: 'Comunitat Valenciana',
            regionBounds: $this->valencianBounds(),
        );
    }

    private function addValencianRegion(): void
    {
        $this->placeFinder->add('region-valenciana', 'Comunitat Valenciana, España', 39.48, -0.75, 'Comunitat Valenciana', $this->valencianBounds(), ['Comunitat Valenciana', 'España'], 'ES');
    }

    private function valencianBounds(): GeoBounds
    {
        return new GeoBounds(new GeoPoint(37.84, -1.53), new GeoPoint(40.79, 0.69));
    }

    private function locatedProperty(string $name): Property
    {
        $property = PropertyMother::from(PropertyId::generate(), $name, $this->account->id(), new PropertyInfo(latitude: '39.4699', longitude: '-0.3763'));
        $this->propertyRepository->add($property);

        return $property;
    }

    private function createSUT(): TripDestinationResolver
    {
        return new TripDestinationResolver(
            $this->placeFinder,
            new TripDestinationCandidates($this->propertyRepository, ConsultantSupervisionPolicyMother::create([$this->account]), new PropertyLocator()),
            $this->countryRepository,
            new PropertyLocator(),
            new IntlCountryRegionCode(),
        );
    }
}
