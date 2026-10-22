<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Domain\Model\Property;

use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\OperatingRegime;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyInfo;
use App\Property\Domain\Model\Property\PropertyRegistered;
use App\Property\Domain\Model\Property\PropertyUpdated;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class PropertyTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testRegisterIsOwnedByDefault(): void
    {
        $property = new Property(id: PropertyId::generate());
        $property->register(name: 'Hotel', accountId: null);

        $this->assertSame(OperatingRegime::Owned, $property->operatingRegime());
    }

    public function testRegisterWithOperatingRegime(): void
    {
        $property = new Property(id: PropertyId::generate());
        $property->register(name: 'Hotel', accountId: null, operatingRegime: OperatingRegime::Franchised);

        $this->assertSame(OperatingRegime::Franchised, $property->operatingRegime());

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertInstanceOf(PropertyRegistered::class, $lastEvent);
        $this->assertSame(OperatingRegime::Franchised, $lastEvent->operatingRegime());
        $this->assertSame('franchised', $lastEvent->payload()['operatingRegime']);
    }

    public function testUpdateWithOperatingRegime(): void
    {
        $property = PropertyMother::registeredProperty();

        $property->update(name: 'Hotel', accountId: null, operatingRegime: OperatingRegime::LeasedOut);

        $this->assertSame(OperatingRegime::LeasedOut, $property->operatingRegime());

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertInstanceOf(PropertyUpdated::class, $lastEvent);
        $this->assertSame(OperatingRegime::LeasedOut, $lastEvent->operatingRegime());
    }

    public function testUpdateWithoutOperatingRegimeKeepsTheCurrentOne(): void
    {
        $property = PropertyMother::registeredProperty();
        $property->update(name: 'Hotel', accountId: null, operatingRegime: OperatingRegime::Franchised);

        $property->update(name: 'Hotel', accountId: null);

        $this->assertSame(OperatingRegime::Franchised, $property->operatingRegime());

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertInstanceOf(PropertyUpdated::class, $lastEvent);
        $this->assertSame(OperatingRegime::Franchised, $lastEvent->operatingRegime());
    }

    public function testMatchKeepsCompanyFiguresAndOperatingRegime(): void
    {
        $accountId = AccountId::generate();
        $companyId = CompanyId::generate();

        $property = new Property(id: PropertyId::generate());
        $property->register(
            name: 'Hotel',
            accountId: $accountId,
            companyId: $companyId,
            numberOfEmployees: 25,
            dailyClients: 120,
            operatingRegime: OperatingRegime::Franchised,
        );

        $property->match('Hotel', new PropertyInfo(address: 'Calle Mayor 1'), new ExternalId(hotelbeds: '123'));

        $updatedEvents = array_values(array_filter($this->spySubscriber->events(), static fn($event): bool => $event instanceof PropertyUpdated));
        $this->assertCount(1, $updatedEvents);
        $lastEvent = $updatedEvents[0];
        $this->assertInstanceOf(PropertyUpdated::class, $lastEvent);
        $this->assertSame((string) $companyId, (string) $lastEvent->companyId());
        $this->assertSame(25, $lastEvent->numberOfEmployees());
        $this->assertSame(120, $lastEvent->dailyClients());
        $this->assertSame(OperatingRegime::Franchised, $lastEvent->operatingRegime());

        $this->assertSame((string) $companyId, (string) $property->companyId());
        $this->assertSame((string) $accountId, (string) $property->accountId());
        $this->assertSame(25, $property->numberOfEmployees());
        $this->assertSame(120, $property->dailyClients());
        $this->assertSame(OperatingRegime::Franchised, $property->operatingRegime());
        $this->assertSame('Calle Mayor 1', $property->info()->address());
        $this->assertSame('123', $property->externalId()->hotelbeds());
    }

    public function testEventsRebuildFromPayloadWithOperatingRegime(): void
    {
        $event = new PropertyUpdated(propertyId: PropertyId::generate(), name: 'Hotel', operatingRegime: OperatingRegime::Managed);

        $rebuilt = PropertyUpdated::rebuildFromPayload($event->id(), $event->occurredOn(), $event->payload());

        $this->assertSame(OperatingRegime::Managed, $rebuilt->operatingRegime());

        $legacy = PropertyRegistered::rebuildFromPayload($event->id(), $event->occurredOn(), ['propertyId' => (string) PropertyId::generate(), 'name' => 'Hotel']);

        $this->assertSame(OperatingRegime::Owned, $legacy->operatingRegime());
    }
}
