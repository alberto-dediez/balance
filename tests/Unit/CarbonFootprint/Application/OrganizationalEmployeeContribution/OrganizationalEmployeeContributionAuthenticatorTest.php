<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OrganizationalEmployeeContribution;

use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionAuthenticator;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionIgnoredException;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionTokenInvalidException;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionRepositoryMother;
use PHPUnit\Framework\TestCase;

class OrganizationalEmployeeContributionAuthenticatorTest extends TestCase
{
    public function testAValidTokenReturnsTheContribution(): void
    {
        $repository = OrganizationalEmployeeContributionRepositoryMother::inMemory();
        $contribution = OrganizationalEmployeeContributionMother::forFootprint(CarbonFootprintReportSubjectType::Property, 'footprint-1', token: 'tok-1');
        $repository->add($contribution);

        $authentication = (new OrganizationalEmployeeContributionAuthenticator($repository))->authenticate('tok-1');

        $this->assertSame($contribution, $authentication->contribution);
    }

    public function testAnUnknownTokenThrows(): void
    {
        $this->expectException(OrganizationalEmployeeContributionTokenInvalidException::class);

        (new OrganizationalEmployeeContributionAuthenticator(OrganizationalEmployeeContributionRepositoryMother::inMemory()))->authenticate('tok-unknown');
    }

    public function testAnIgnoredContributionThrows(): void
    {
        $repository = OrganizationalEmployeeContributionRepositoryMother::inMemory();
        $contribution = OrganizationalEmployeeContributionMother::forFootprint(CarbonFootprintReportSubjectType::Property, 'footprint-1', token: 'tok-1');
        $contribution->ignore();
        $repository->add($contribution);

        $this->expectException(OrganizationalEmployeeContributionIgnoredException::class);

        (new OrganizationalEmployeeContributionAuthenticator($repository))->authenticate('tok-1');
    }
}
