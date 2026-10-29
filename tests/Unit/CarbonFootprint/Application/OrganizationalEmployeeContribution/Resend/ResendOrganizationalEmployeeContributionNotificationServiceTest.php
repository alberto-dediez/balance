<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OrganizationalEmployeeContribution\Resend;

use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\Resend\ResendOrganizationalEmployeeContributionNotificationRequest;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\Resend\ResendOrganizationalEmployeeContributionNotificationService;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContribution;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionNotificationResent;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionClosedException;
use App\Security\Application\UnauthorizedActionException;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionServiceTestCase;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionMother;

class ResendOrganizationalEmployeeContributionNotificationServiceTest extends OrganizationalEmployeeContributionServiceTestCase
{
    private ResendOrganizationalEmployeeContributionNotificationService $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = new ResendOrganizationalEmployeeContributionNotificationService($this->contributionRepository, $this->authorizer, $this->locator);
    }

    public function testTheOwnerCanDoIt(): void
    {
        $contribution = $this->contribution((string) $this->propertyFootprint()->id());
        $contribution->clearEvents();

        ($this->sut)(new ResendOrganizationalEmployeeContributionNotificationRequest($contribution->id()), $this->ownerUser());

        $this->assertCount(1, array_filter($contribution->recordedEvents(), static fn($event) => $event instanceof OrganizationalEmployeeContributionNotificationResent));
    }

    public function testACompletedFootprintThrows(): void
    {
        $contribution = $this->contribution((string) $this->completedPropertyFootprint()->id());

        $this->expectException(OrganizationalProviderContributionClosedException::class);

        ($this->sut)(new ResendOrganizationalEmployeeContributionNotificationRequest($contribution->id()), $this->ownerUser());
    }

    public function testUserIsDenied(): void
    {
        $contribution = $this->contribution((string) $this->propertyFootprint()->id());

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new ResendOrganizationalEmployeeContributionNotificationRequest($contribution->id()), $this->user());
    }

    public function testAnIgnoredContributionCannotBeResent(): void
    {
        $contribution = $this->contribution((string) $this->propertyFootprint()->id());
        $contribution->ignore();

        $this->expectException(\LogicException::class);

        ($this->sut)(new ResendOrganizationalEmployeeContributionNotificationRequest($contribution->id()), $this->ownerUser());
    }

    private function contribution(string $footprintId): OrganizationalEmployeeContribution
    {
        $contribution = OrganizationalEmployeeContributionMother::forFootprint(CarbonFootprintReportSubjectType::Property, $footprintId, self::ACCOUNT_ID);
        $this->contributionRepository->add($contribution);

        return $contribution;
    }
}
