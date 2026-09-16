<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprint;

use App\CarbonFootprint\Application\CarbonFootprint\CarbonFootprintCountryResolver;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyInfo;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use PHPUnit\Framework\TestCase;

class CarbonFootprintCountryResolverTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testReturnsNullWhenTheFootprintHasNoEvent(): void
    {
        $sut = $this->createSUT(EventRepositoryMother::inMemory(), PropertyRepositoryMother::inMemory());

        $this->assertNull($sut->resolve(new EventCarbonFootprint(id: EventCarbonFootprintId::generate())));
    }

    public function testReturnsNullWhenTheEventIsNotInTheRepository(): void
    {
        $sut = $this->createSUT(EventRepositoryMother::inMemory(), PropertyRepositoryMother::inMemory());

        $this->assertNull($sut->resolve(EventCarbonFootprintMother::random()));
    }

    public function testReturnsNullWhenTheEventHasNoProperty(): void
    {
        $eventRepository = EventRepositoryMother::inMemory();
        $eventRepository->add(EventMother::from(name: 'Event', id: 'E-1'));

        $sut = $this->createSUT($eventRepository, PropertyRepositoryMother::inMemory());

        $this->assertNull($sut->resolve($this->footprintForEvent('E-1')));
    }

    public function testReturnsNullWhenThePropertyIsNotInTheRepository(): void
    {
        $eventRepository = EventRepositoryMother::inMemory();
        $eventRepository->add(EventMother::from(name: 'Event', id: 'E-1', propertyId: 'P-1'));

        $sut = $this->createSUT($eventRepository, PropertyRepositoryMother::inMemory());

        $this->assertNull($sut->resolve($this->footprintForEvent('E-1')));
    }

    public function testReturnsNullWhenThePropertyHasNoCountry(): void
    {
        $eventRepository = EventRepositoryMother::inMemory();
        $eventRepository->add(EventMother::from(name: 'Event', id: 'E-1', propertyId: 'P-1'));

        $propertyRepository = PropertyRepositoryMother::inMemory();
        $propertyRepository->add(PropertyMother::from(propertyId: new PropertyId('P-1'), info: new PropertyInfo()));

        $sut = $this->createSUT($eventRepository, $propertyRepository);

        $this->assertNull($sut->resolve($this->footprintForEvent('E-1')));
    }

    public function testReturnsTheCountryOfTheProperty(): void
    {
        $eventRepository = EventRepositoryMother::inMemory();
        $eventRepository->add(EventMother::from(name: 'Event', id: 'E-1', propertyId: 'P-1'));

        $propertyRepository = PropertyRepositoryMother::inMemory();
        $propertyRepository->add(PropertyMother::from(
            propertyId: new PropertyId('P-1'),
            info: new PropertyInfo(countryId: new CountryId('C-ES')),
        ));

        $sut = $this->createSUT($eventRepository, $propertyRepository);

        $this->assertSame('C-ES', (string) $sut->resolve($this->footprintForEvent('E-1')));
    }

    public function testReturnsTheCountryOfThePropertyOfAPropertyFootprint(): void
    {
        $propertyRepository = PropertyRepositoryMother::inMemory();
        $propertyRepository->add(PropertyMother::from(
            propertyId: new PropertyId('P-1'),
            info: new PropertyInfo(countryId: new CountryId('C-ES')),
        ));

        $sut = $this->createSUT(EventRepositoryMother::inMemory(), $propertyRepository);
        $footprint = PropertyCarbonFootprintMother::from('P-1', (string) PropertyCarbonFootprintId::generate());

        $this->assertSame('C-ES', (string) $sut->resolve($footprint));
    }

    public function testReturnsNullForAPropertyFootprintWhosePropertyHasNoCountry(): void
    {
        $propertyRepository = PropertyRepositoryMother::inMemory();
        $propertyRepository->add(PropertyMother::from(propertyId: new PropertyId('P-1'), info: new PropertyInfo()));

        $sut = $this->createSUT(EventRepositoryMother::inMemory(), $propertyRepository);
        $footprint = PropertyCarbonFootprintMother::from('P-1', (string) PropertyCarbonFootprintId::generate());

        $this->assertNull($sut->resolve($footprint));
    }

    public function testReturnsTheCountryOfTheBlockPropertyOfACompanyFootprint(): void
    {
        $propertyRepository = PropertyRepositoryMother::inMemory();
        $propertyRepository->add(PropertyMother::from(
            propertyId: new PropertyId('P-1'),
            info: new PropertyInfo(countryId: new CountryId('C-ES')),
        ));

        $sut = $this->createSUT(EventRepositoryMother::inMemory(), $propertyRepository);
        $footprint = CompanyCarbonFootprintMother::from('C-1', (string) CompanyCarbonFootprintId::generate());

        $this->assertSame('C-ES', (string) $sut->resolve($footprint, new PropertyId('P-1')));
    }

    public function testReturnsNullForTheUnassignedBlockOfACompanyFootprint(): void
    {
        $propertyRepository = PropertyRepositoryMother::inMemory();
        $propertyRepository->add(PropertyMother::from(
            propertyId: new PropertyId('P-1'),
            info: new PropertyInfo(countryId: new CountryId('C-ES')),
        ));

        $sut = $this->createSUT(EventRepositoryMother::inMemory(), $propertyRepository);
        $footprint = CompanyCarbonFootprintMother::from('C-1', (string) CompanyCarbonFootprintId::generate());

        $this->assertNull($sut->resolve($footprint));
    }

    private function footprintForEvent(string $eventId): EventCarbonFootprint
    {
        return EventCarbonFootprintMother::from(eventId: $eventId, id: (string) EventCarbonFootprintId::generate());
    }

    private function createSUT(EventRepository $eventRepository, PropertyRepository $propertyRepository): CarbonFootprintCountryResolver
    {
        return new CarbonFootprintCountryResolver($eventRepository, $propertyRepository);
    }
}
