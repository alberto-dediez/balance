<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Domain\Model\Property;

use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\OperatingRegime;
use App\Property\Domain\Model\Property\OperatorContact;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyAccountReleased;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyInfo;
use App\Property\Domain\Model\Property\PropertyOperatorContactUpdated;
use App\Property\Domain\Model\Property\PropertyRegistered;
use App\Property\Domain\Model\Property\PropertyUpdated;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
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

    public function testRegisterWithOperatorContact(): void
    {
        $contact = OperatorContact::from('franquicia@hotel.es', 'Ana', Locale::English);

        $property = new Property(id: PropertyId::generate());
        $property->register(name: 'Hotel', accountId: null, operatorContact: $contact);

        $this->assertTrue($property->operatorContact()->equals($contact));

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertInstanceOf(PropertyRegistered::class, $lastEvent);
        $this->assertSame('franquicia@hotel.es', $lastEvent->payload()['operatorEmail']);
        $this->assertSame('Ana', $lastEvent->payload()['operatorName']);
        $this->assertSame('en', $lastEvent->payload()['operatorLocale']);
    }

    public function testRegisterWithoutOperatorContactHasAnEmptyOne(): void
    {
        $property = PropertyMother::registeredProperty();

        $this->assertTrue($property->operatorContact()->isEmpty());
    }

    public function testUpdateWithOperatorContact(): void
    {
        $property = PropertyMother::registeredProperty();

        $property->update(name: 'Hotel', accountId: null, operatorContact: OperatorContact::from('franquicia@hotel.es', null, null));

        $this->assertSame('franquicia@hotel.es', $property->operatorContact()->email());
    }

    public function testUpdateWithoutOperatorContactKeepsTheCurrentOne(): void
    {
        $property = PropertyMother::from(propertyId: PropertyId::generate(), operatorContact: OperatorContact::from('franquicia@hotel.es', 'Ana', null));

        $property->update(name: 'Hotel', accountId: null);

        $this->assertSame('franquicia@hotel.es', $property->operatorContact()->email());
        $this->assertSame('Ana', $property->operatorContact()->name());
    }

    public function testUpdateWithAnEmptyOperatorContactClearsIt(): void
    {
        $property = PropertyMother::from(propertyId: PropertyId::generate(), operatorContact: OperatorContact::from('franquicia@hotel.es', 'Ana', null));

        $property->update(name: 'Hotel', accountId: null, operatorContact: new OperatorContact());

        $this->assertTrue($property->operatorContact()->isEmpty());
    }

    public function testUpdateOperatorContactPublishesTheEventWhenItChanges(): void
    {
        $property = PropertyMother::registeredProperty();

        $property->updateOperatorContact(OperatorContact::from('franquicia@hotel.es', 'Ana', Locale::Spanish));

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertInstanceOf(PropertyOperatorContactUpdated::class, $lastEvent);
        $this->assertSame('franquicia@hotel.es', $lastEvent->payload()['operatorEmail']);
        $this->assertSame('franquicia@hotel.es', $property->operatorContact()->email());
        $this->assertSame(Locale::Spanish, $property->operatorContact()->locale());
    }

    public function testUpdateOperatorContactDoesNothingWhenItDoesNotChange(): void
    {
        $property = PropertyMother::from(propertyId: PropertyId::generate(), operatorContact: OperatorContact::from('franquicia@hotel.es', 'Ana', null));
        $eventsBefore = count($this->spySubscriber->events());

        $property->updateOperatorContact(OperatorContact::from(' franquicia@hotel.es ', 'Ana', null));

        $this->assertCount($eventsBefore, $this->spySubscriber->events());
    }

    public function testMatchKeepsTheOperatorContact(): void
    {
        $property = PropertyMother::from(propertyId: PropertyId::generate(), operatorContact: OperatorContact::from('franquicia@hotel.es', 'Ana', null));

        $property->match('Hotel', new PropertyInfo(address: 'Calle Mayor 1'), new ExternalId(hotelbeds: '123'));

        $updatedEvents = array_values(array_filter($this->spySubscriber->events(), static fn($event): bool => $event instanceof PropertyUpdated));
        $this->assertCount(1, $updatedEvents);
        $this->assertInstanceOf(PropertyUpdated::class, $updatedEvents[0]);
        $this->assertSame('franquicia@hotel.es', $updatedEvents[0]->operatorContact()?->email());
        $this->assertSame('franquicia@hotel.es', $property->operatorContact()->email());
    }

    public function testEventsRebuildFromPayloadWithOperatorContact(): void
    {
        $event = new PropertyOperatorContactUpdated(propertyId: PropertyId::generate(), operatorContact: OperatorContact::from('franquicia@hotel.es', 'Ana', Locale::French));

        $rebuilt = PropertyOperatorContactUpdated::rebuildFromPayload($event->id(), $event->occurredOn(), $event->payload());

        $this->assertTrue($rebuilt->operatorContact()->equals($event->operatorContact()));

        $registered = new PropertyRegistered(propertyId: PropertyId::generate(), name: 'Hotel', operatorContact: OperatorContact::from('franquicia@hotel.es', null, Locale::German));

        $rebuiltRegistered = PropertyRegistered::rebuildFromPayload($registered->id(), $registered->occurredOn(), $registered->payload());

        $this->assertNotNull($rebuiltRegistered->operatorContact());
        $this->assertSame('franquicia@hotel.es', $rebuiltRegistered->operatorContact()->email());
        $this->assertSame(Locale::German, $rebuiltRegistered->operatorContact()->locale());
    }

    public function testReleaseAccountClearsAccountAndCompanyKeepingTheRest(): void
    {
        $info = new PropertyInfo(address: 'Calle Mayor 1', rooms: 40);
        $externalId = new ExternalId(hotelbeds: 'HB-1');

        $property = PropertyMother::from(
            propertyId: PropertyId::generate(),
            name: 'Hotel',
            accountId: AccountId::generate(),
            info: $info,
            externalId: $externalId,
            companyId: CompanyId::generate(),
            numberOfEmployees: 25,
            dailyClients: 300,
        );

        $property->releaseAccount();

        $this->assertNull($property->accountId());
        $this->assertNull($property->companyId());
        $this->assertSame('Hotel', $property->name());
        $this->assertTrue($info->equals($property->info()));
        $this->assertTrue($externalId->equals($property->externalId()));
        $this->assertSame(25, $property->numberOfEmployees());
        $this->assertSame(300, $property->dailyClients());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(PropertyAccountReleased::class, $lastEvent);
        $this->assertTrue($property->id()->equals($lastEvent->propertyId()));
    }

    public function testReleaseAccountClearsOrphanCompany(): void
    {
        $property = PropertyMother::from(
            propertyId: PropertyId::generate(),
            name: 'Hotel',
            companyId: CompanyId::generate(),
        );

        $property->releaseAccount();

        $this->assertNull($property->accountId());
        $this->assertNull($property->companyId());
        $this->assertInstanceOf(PropertyAccountReleased::class, $this->spySubscriber->lastEvent());
    }

    public function testReleaseAccountWithoutAccountNorCompanyPublishesNothing(): void
    {
        $property = PropertyMother::from(propertyId: PropertyId::generate(), name: 'Hotel');

        $eventsBefore = count($this->spySubscriber->events());

        $property->releaseAccount();

        $this->assertCount($eventsBefore, $this->spySubscriber->events());
        $this->assertNull($property->accountId());
        $this->assertNull($property->companyId());
    }

    public function testMatchKeepsCompanyEmployeesAndDailyClients(): void
    {
        $accountId = AccountId::generate();
        $companyId = CompanyId::generate();

        $property = PropertyMother::from(
            propertyId: PropertyId::generate(),
            name: 'Hotel',
            accountId: $accountId,
            info: new PropertyInfo(address: 'Calle Mayor 1'),
            companyId: $companyId,
            numberOfEmployees: 25,
            dailyClients: 300,
        );

        $property->match(
            name: 'Hotel',
            info: new PropertyInfo(address: 'Calle Mayor 1', web: 'https://hotel.example'),
            externalId: new ExternalId(),
        );

        $updatedEvents = array_filter($this->spySubscriber->events(), static fn($event) => $event instanceof PropertyUpdated);

        $this->assertCount(1, $updatedEvents);
        $this->assertSame('https://hotel.example', $property->info()->web());
        $this->assertNotNull($property->accountId());
        $this->assertTrue($accountId->equals($property->accountId()));
        $this->assertNotNull($property->companyId());
        $this->assertTrue($companyId->equals($property->companyId()));
        $this->assertSame(25, $property->numberOfEmployees());
        $this->assertSame(300, $property->dailyClients());
    }
}
