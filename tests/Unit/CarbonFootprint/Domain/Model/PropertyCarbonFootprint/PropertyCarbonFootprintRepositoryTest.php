<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintStatus;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintFilter;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class PropertyCarbonFootprintRepositoryTest extends KernelTestCase
{
    abstract protected function createSUT(): PropertyCarbonFootprintRepository;

    public function testAddAndFind(): void
    {
        $repository = $this->createSUT();
        $footprint = PropertyCarbonFootprintMother::registered();

        $this->assertNull($repository->find($footprint->id()));

        $repository->add($footprint);

        $recovered = $repository->find($footprint->id());
        $this->assertNotNull($recovered);
        $this->assertEquals($footprint->id(), $recovered->id());
    }

    public function testRemove(): void
    {
        $repository = $this->createSUT();
        $footprint = PropertyCarbonFootprintMother::registered();
        $repository->add($footprint);

        $repository->remove($footprint);

        $this->assertNull($repository->find($footprint->id()));
    }

    public function testFindByPropertyIdAndYearIgnoresRemoved(): void
    {
        $repository = $this->createSUT();
        $propertyId = (string) PropertyId::generate();
        $footprint = PropertyCarbonFootprintMother::from($propertyId, (string) PropertyCarbonFootprintId::generate(), year: 2024);
        $repository->add($footprint);

        $this->assertNotNull($repository->findByPropertyIdAndYear(new PropertyId($propertyId), 2024));
        $this->assertNull($repository->findByPropertyIdAndYear(new PropertyId($propertyId), 2023));

        $footprint->remove();
        $repository->add($footprint);

        $this->assertNull($repository->findByPropertyIdAndYear(new PropertyId($propertyId), 2024));
    }

    public function testFindByPropertyId(): void
    {
        $repository = $this->createSUT();
        $propertyId = (string) PropertyId::generate();

        $repository->add(PropertyCarbonFootprintMother::from($propertyId, (string) PropertyCarbonFootprintId::generate(), year: 2023));
        $repository->add(PropertyCarbonFootprintMother::from($propertyId, (string) PropertyCarbonFootprintId::generate(), year: 2024));
        $repository->add(PropertyCarbonFootprintMother::registered());

        $this->assertCount(2, $repository->findByPropertyId(new PropertyId($propertyId)));
    }

    public function testFindByActivityId(): void
    {
        $repository = $this->createSUT();
        $footprint = PropertyCarbonFootprintMother::registered();
        $activityId = ActivityId::generate();
        $footprint->addActivity($activityId, new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
        $repository->add($footprint);

        $found = $repository->findByActivityId($activityId);

        $this->assertNotNull($found);
        $this->assertEquals($footprint->id(), $found->id());
        $this->assertNull($repository->findByActivityId(ActivityId::generate()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();
        $propertyId = (string) PropertyId::generate();

        $answered = PropertyCarbonFootprintMother::withScope1Answered($propertyId, (string) PropertyCarbonFootprintId::generate(), 'account-one', 2024);
        $repository->add($answered);
        $repository->add(PropertyCarbonFootprintMother::from((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate(), 'account-one', 2023));
        $repository->add(PropertyCarbonFootprintMother::from((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate(), 'account-two', 2024));

        $this->assertCount(3, $repository->findByFilter(new PropertyCarbonFootprintFilter()));
        $this->assertCount(2, $repository->findByFilter(new PropertyCarbonFootprintFilter(accountId: new AccountId('account-one'))));
        $this->assertCount(1, $repository->findByFilter(new PropertyCarbonFootprintFilter(propertyId: new PropertyId($propertyId))));
        $this->assertCount(2, $repository->findByFilter(new PropertyCarbonFootprintFilter(year: 2024)));
        $this->assertCount(1, $repository->findByFilter(new PropertyCarbonFootprintFilter(status: OrganizationalCarbonFootprintStatus::Scope1Answered)));
        $this->assertSame(2, $repository->totalResultsByFilter(new PropertyCarbonFootprintFilter(accountId: new AccountId('account-one'))));
    }
}
