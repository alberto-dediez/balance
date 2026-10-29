<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OrganizationalEmployeeContribution\Register;

use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\Register\RegisterOrganizationalEmployeeContributionRequest;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\Security\Application\UnauthorizedActionException;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionServiceTestCase;

class RegisterOrganizationalEmployeeContributionServiceTest extends OrganizationalEmployeeContributionServiceTestCase
{
    public function testRegistersAPendingContributionWithTheEmployeeName(): void
    {
        $footprint = $this->propertyFootprint();
        $employee = $this->employee(name: 'Lucía');

        $response = ($this->registerService())(
            new RegisterOrganizationalEmployeeContributionRequest(CarbonFootprintReportSubjectType::Property, (string) $footprint->id(), $employee->id()),
            $this->ownerUser(),
        );

        $contribution = $this->contributionRepository->find($response->contribution->id());
        $this->assertNotNull($contribution);
        $this->assertSame('Lucía', $contribution->employeeName());
        $this->assertTrue($contribution->status()->isPending());
        $this->assertSame(CarbonFootprintReportSubjectType::Property, $contribution->subjectType());
        $this->assertSame((string) $footprint->id(), $contribution->carbonFootprintId());
        $this->assertSame(self::ACCOUNT_ID, (string) $contribution->accountId());
    }

    public function testAnEmployeeOfAnotherAccountThrows(): void
    {
        $footprint = $this->propertyFootprint();
        $employee = $this->employee(accountId: 'other-account');

        $this->expectException(\InvalidArgumentException::class);

        ($this->registerService())(
            new RegisterOrganizationalEmployeeContributionRequest(CarbonFootprintReportSubjectType::Property, (string) $footprint->id(), $employee->id()),
            $this->ownerUser(),
        );
    }

    public function testUserIsDenied(): void
    {
        $footprint = $this->propertyFootprint();
        $employee = $this->employee();

        $this->expectException(UnauthorizedActionException::class);

        ($this->registerService())(
            new RegisterOrganizationalEmployeeContributionRequest(CarbonFootprintReportSubjectType::Property, (string) $footprint->id(), $employee->id()),
            $this->user(),
        );
    }
}
