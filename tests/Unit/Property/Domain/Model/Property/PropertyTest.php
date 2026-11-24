<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Domain\Model\Property;

use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\PropertyAccountReleased;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyInfo;
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
