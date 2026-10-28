<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\Ignore;

use App\CarbonFootprint\Application\OrganizationalProviderContribution\Ignore\IgnoreOrganizationalProviderContributionRequest;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\Ignore\IgnoreOrganizationalProviderContributionService;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\Ignore\UnignoreOrganizationalProviderContributionRequest;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\Ignore\UnignoreOrganizationalProviderContributionService;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionClosedException;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionStatus;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\UnauthorizedActionException;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalProviderContributionServiceTestCase;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;

class IgnoreOrganizationalProviderContributionServiceTest extends OrganizationalProviderContributionServiceTestCase
{
    public function testIgnoreAndUnignore(): void
    {
        $footprint = $this->propertyFootprint();
        $contribution = OrganizationalProviderContributionMother::requested(carbonFootprintId: (string) $footprint->id());
        $this->contributionRepository->add($contribution);

        $ignore = new IgnoreOrganizationalProviderContributionService($this->contributionRepository, $this->authorizer, $this->locator);
        $ignore(new IgnoreOrganizationalProviderContributionRequest($contribution->id()), $this->owner());

        $this->assertSame(ProviderContributionStatus::Ignored, $contribution->status());

        $unignore = new UnignoreOrganizationalProviderContributionService($this->contributionRepository, $this->authorizer, $this->locator);
        $unignore(new UnignoreOrganizationalProviderContributionRequest($contribution->id()), $this->owner());

        $this->assertSame(ProviderContributionStatus::Pending, $contribution->status());
    }

    public function testCompletedFootprintThrows(): void
    {
        $footprint = PropertyCarbonFootprintMother::withProvidersAnswered((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate(), self::ACCOUNT_ID);
        $footprint->complete(new Emissions('1', '1', '0', '0'));
        $this->propertyCarbonFootprintRepository->add($footprint);
        $contribution = OrganizationalProviderContributionMother::requested(carbonFootprintId: (string) $footprint->id());
        $this->contributionRepository->add($contribution);

        $this->expectException(OrganizationalProviderContributionClosedException::class);

        (new IgnoreOrganizationalProviderContributionService($this->contributionRepository, $this->authorizer, $this->locator))(
            new IgnoreOrganizationalProviderContributionRequest($contribution->id()),
            $this->owner(),
        );
    }

    public function testUserIsDenied(): void
    {
        $footprint = $this->propertyFootprint();
        $contribution = OrganizationalProviderContributionMother::requested(carbonFootprintId: (string) $footprint->id());
        $this->contributionRepository->add($contribution);

        $this->expectException(UnauthorizedActionException::class);

        (new IgnoreOrganizationalProviderContributionService($this->contributionRepository, $this->authorizer, $this->locator))(
            new IgnoreOrganizationalProviderContributionRequest($contribution->id()),
            $this->plainUser(),
        );
    }

    public function testUnignoreOnCompletedFootprintThrows(): void
    {
        $footprint = $this->completedPropertyFootprint();
        $contribution = OrganizationalProviderContributionMother::requested(carbonFootprintId: (string) $footprint->id());
        $contribution->ignore();
        $this->contributionRepository->add($contribution);

        $this->expectException(OrganizationalProviderContributionClosedException::class);

        (new UnignoreOrganizationalProviderContributionService($this->contributionRepository, $this->authorizer, $this->locator))(
            new UnignoreOrganizationalProviderContributionRequest($contribution->id()),
            $this->owner(),
        );
    }

    public function testUnignoreUserIsDenied(): void
    {
        $footprint = $this->propertyFootprint();
        $contribution = OrganizationalProviderContributionMother::requested(carbonFootprintId: (string) $footprint->id());
        $contribution->ignore();
        $this->contributionRepository->add($contribution);

        $this->expectException(UnauthorizedActionException::class);

        (new UnignoreOrganizationalProviderContributionService($this->contributionRepository, $this->authorizer, $this->locator))(
            new UnignoreOrganizationalProviderContributionRequest($contribution->id()),
            $this->plainUser(),
        );
    }

    public function testUnignoreOwnerOfAnotherAccountIsDenied(): void
    {
        $footprint = $this->propertyFootprint();
        $contribution = OrganizationalProviderContributionMother::requested(carbonFootprintId: (string) $footprint->id());
        $contribution->ignore();
        $this->contributionRepository->add($contribution);

        $this->expectException(UnauthorizedActionException::class);

        (new UnignoreOrganizationalProviderContributionService($this->contributionRepository, $this->authorizer, $this->locator))(
            new UnignoreOrganizationalProviderContributionRequest($contribution->id()),
            $this->owner('other-account'),
        );
    }
}
