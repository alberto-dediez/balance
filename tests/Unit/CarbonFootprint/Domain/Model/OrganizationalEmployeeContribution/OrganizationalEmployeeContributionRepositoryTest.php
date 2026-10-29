<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionRepository;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use PHPUnit\Framework\TestCase;

abstract class OrganizationalEmployeeContributionRepositoryTest extends TestCase
{
    public function testAddContributionToRepository(): void
    {
        $repository = $this->createSUT();

        $contribution = OrganizationalEmployeeContributionMother::registered();

        $this->assertNull($repository->find($contribution->id()));

        $repository->add($contribution);

        $recovered = $repository->find($contribution->id());

        $this->assertNotNull($recovered);
        $this->assertEquals($contribution->id(), $recovered->id());
    }

    public function testFindByToken(): void
    {
        $repository = $this->createSUT();

        $contribution = OrganizationalEmployeeContributionMother::forFootprint(CarbonFootprintReportSubjectType::Property, 'footprint-1', token: 'tok-1');
        $repository->add($contribution);

        $recovered = $repository->findByToken('tok-1');

        $this->assertNotNull($recovered);
        $this->assertEquals($contribution->id(), $recovered->id());
        $this->assertNull($repository->findByToken('tok-unknown'));
    }

    public function testFindByCarbonFootprint(): void
    {
        $repository = $this->createSUT();

        $repository->add(OrganizationalEmployeeContributionMother::forFootprint(CarbonFootprintReportSubjectType::Company, 'footprint-1'));
        $repository->add(OrganizationalEmployeeContributionMother::forFootprint(CarbonFootprintReportSubjectType::Company, 'footprint-1'));
        $repository->add(OrganizationalEmployeeContributionMother::forFootprint(CarbonFootprintReportSubjectType::Property, 'footprint-1'));

        $this->assertCount(2, $repository->findByCarbonFootprint(CarbonFootprintReportSubjectType::Company, 'footprint-1'));
        $this->assertCount(1, $repository->findByCarbonFootprint(CarbonFootprintReportSubjectType::Property, 'footprint-1'));
        $this->assertCount(0, $repository->findByCarbonFootprint(CarbonFootprintReportSubjectType::Company, 'footprint-2'));
    }

    public function testFindByCarbonFootprintAndEmployee(): void
    {
        $repository = $this->createSUT();
        $employeeId = (string) EmployeeId::generate();

        $contribution = OrganizationalEmployeeContributionMother::forFootprint(CarbonFootprintReportSubjectType::Property, 'footprint-1', employeeId: $employeeId);
        $repository->add($contribution);
        $repository->add(OrganizationalEmployeeContributionMother::forFootprint(CarbonFootprintReportSubjectType::Property, 'footprint-1'));

        $recovered = $repository->findByCarbonFootprintAndEmployee(CarbonFootprintReportSubjectType::Property, 'footprint-1', new EmployeeId($employeeId));

        $this->assertNotNull($recovered);
        $this->assertEquals($contribution->id(), $recovered->id());
        $this->assertNull($repository->findByCarbonFootprintAndEmployee(CarbonFootprintReportSubjectType::Property, 'footprint-2', new EmployeeId($employeeId)));
    }

    abstract protected function createSUT(): OrganizationalEmployeeContributionRepository;
}
