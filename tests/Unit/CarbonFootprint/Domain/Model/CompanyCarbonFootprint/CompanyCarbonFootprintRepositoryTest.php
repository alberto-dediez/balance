<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintFilter;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintStatus;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Property\Domain\Model\Company\CompanyId;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class CompanyCarbonFootprintRepositoryTest extends KernelTestCase
{
    abstract protected function createSUT(): CompanyCarbonFootprintRepository;

    public function testAddAndFind(): void
    {
        $repository = $this->createSUT();
        $footprint = CompanyCarbonFootprintMother::registered();

        $this->assertNull($repository->find($footprint->id()));

        $repository->add($footprint);

        $recovered = $repository->find($footprint->id());
        $this->assertNotNull($recovered);
        $this->assertEquals($footprint->id(), $recovered->id());
    }

    public function testRemove(): void
    {
        $repository = $this->createSUT();
        $footprint = CompanyCarbonFootprintMother::registered();
        $repository->add($footprint);

        $repository->remove($footprint);

        $this->assertNull($repository->find($footprint->id()));
    }

    public function testFindByCompanyIdAndYearIgnoresRemoved(): void
    {
        $repository = $this->createSUT();
        $companyId = (string) CompanyId::generate();
        $footprint = CompanyCarbonFootprintMother::from($companyId, (string) CompanyCarbonFootprintId::generate(), year: 2024);
        $repository->add($footprint);

        $this->assertNotNull($repository->findByCompanyIdAndYear(new CompanyId($companyId), 2024));
        $this->assertNull($repository->findByCompanyIdAndYear(new CompanyId($companyId), 2023));

        $footprint->remove();
        $repository->add($footprint);

        $this->assertNull($repository->findByCompanyIdAndYear(new CompanyId($companyId), 2024));
    }

    public function testFindByPropertyId(): void
    {
        $repository = $this->createSUT();
        $companyId = (string) CompanyId::generate();

        $repository->add(CompanyCarbonFootprintMother::from($companyId, (string) CompanyCarbonFootprintId::generate(), year: 2023));
        $repository->add(CompanyCarbonFootprintMother::from($companyId, (string) CompanyCarbonFootprintId::generate(), year: 2024));
        $repository->add(CompanyCarbonFootprintMother::registered());

        $this->assertCount(2, $repository->findByCompanyId(new CompanyId($companyId)));
    }

    public function testFindByActivityId(): void
    {
        $repository = $this->createSUT();
        $footprint = CompanyCarbonFootprintMother::registered();
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
        $companyId = (string) CompanyId::generate();

        $answered = CompanyCarbonFootprintMother::withScope1Answered($companyId, (string) CompanyCarbonFootprintId::generate(), 'account-one', 2024);
        $repository->add($answered);
        $repository->add(CompanyCarbonFootprintMother::from((string) CompanyId::generate(), (string) CompanyCarbonFootprintId::generate(), 'account-one', 2023));
        $repository->add(CompanyCarbonFootprintMother::from((string) CompanyId::generate(), (string) CompanyCarbonFootprintId::generate(), 'account-two', 2024));

        $this->assertCount(3, $repository->findByFilter(new CompanyCarbonFootprintFilter()));
        $this->assertCount(2, $repository->findByFilter(new CompanyCarbonFootprintFilter(accountId: new AccountId('account-one'))));
        $this->assertCount(1, $repository->findByFilter(new CompanyCarbonFootprintFilter(companyId: new CompanyId($companyId))));
        $this->assertCount(2, $repository->findByFilter(new CompanyCarbonFootprintFilter(year: 2024)));
        $this->assertCount(1, $repository->findByFilter(new CompanyCarbonFootprintFilter(status: OrganizationalCarbonFootprintStatus::Scope1Answered)));
        $this->assertSame(2, $repository->totalResultsByFilter(new CompanyCarbonFootprintFilter(accountId: new AccountId('account-one'))));
    }

    public function testFindCompletedByYear(): void
    {
        $repository = $this->createSUT();

        $completed = CompanyCarbonFootprintMother::withEmployeesAnswered('subject-1', 'footprint-1', 'account-one', 2024);
        $completed->complete(new Emissions('100', '100'));
        $otherAccount = CompanyCarbonFootprintMother::withEmployeesAnswered('subject-2', 'footprint-2', 'account-two', 2024);
        $otherAccount->complete(new Emissions('200', '200'));
        $removed = CompanyCarbonFootprintMother::withEmployeesAnswered('subject-3', 'footprint-3', 'account-one', 2024);
        $removed->complete(new Emissions('300', '300'));
        $removed->remove();
        $otherYear = CompanyCarbonFootprintMother::withEmployeesAnswered('subject-4', 'footprint-4', 'account-one', 2023);
        $otherYear->complete(new Emissions('400', '400'));

        $repository->add($completed);
        $repository->add($otherAccount);
        $repository->add($removed);
        $repository->add($otherYear);
        $repository->add(CompanyCarbonFootprintMother::withEmployeesAnswered('subject-5', 'footprint-5', 'account-one', 2024));

        $ids = array_map(static fn($footprint): string => (string) $footprint->id(), $repository->findCompletedByYear(2024));
        sort($ids);

        $this->assertSame(['footprint-1', 'footprint-2'], $ids);
    }
}
