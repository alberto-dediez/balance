<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OrganizationalEmployeeContribution\Ignore;

use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\Ignore\UnignoreOrganizationalEmployeeContributionRequest;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\Ignore\UnignoreOrganizationalEmployeeContributionService;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContribution;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionClosedException;
use App\Security\Application\UnauthorizedActionException;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionServiceTestCase;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionMother;

class UnignoreOrganizationalEmployeeContributionServiceTest extends OrganizationalEmployeeContributionServiceTestCase
{
    private UnignoreOrganizationalEmployeeContributionService $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = new UnignoreOrganizationalEmployeeContributionService($this->contributionRepository, $this->authorizer, $this->locator);
    }

    public function testTheOwnerCanDoIt(): void
    {
        $contribution = $this->contribution((string) $this->propertyFootprint()->id());
        $contribution->ignore();
        $contribution->clearEvents();

        ($this->sut)(new UnignoreOrganizationalEmployeeContributionRequest($contribution->id()), $this->ownerUser());

        $this->assertTrue($contribution->status()->isPending());
    }

    public function testACompletedFootprintThrows(): void
    {
        $contribution = $this->contribution((string) $this->completedPropertyFootprint()->id());
        $contribution->ignore();

        $this->expectException(OrganizationalProviderContributionClosedException::class);

        ($this->sut)(new UnignoreOrganizationalEmployeeContributionRequest($contribution->id()), $this->ownerUser());
    }

    public function testUserIsDenied(): void
    {
        $contribution = $this->contribution((string) $this->propertyFootprint()->id());

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new UnignoreOrganizationalEmployeeContributionRequest($contribution->id()), $this->user());
    }

    private function contribution(string $footprintId): OrganizationalEmployeeContribution
    {
        $contribution = OrganizationalEmployeeContributionMother::forFootprint(CarbonFootprintReportSubjectType::Property, $footprintId, self::ACCOUNT_ID);
        $this->contributionRepository->add($contribution);

        return $contribution;
    }
}
