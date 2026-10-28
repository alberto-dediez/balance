<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\Remove;

use App\CarbonFootprint\Application\OrganizationalProviderContribution\Remove\RemoveOrganizationalProviderContributionRequest;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\Remove\RemoveOrganizationalProviderContributionService;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionClosedException;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Security\Application\UnauthorizedActionException;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalProviderContributionServiceTestCase;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionMother;

class RemoveOrganizationalProviderContributionServiceTest extends OrganizationalProviderContributionServiceTestCase
{
    private RemoveOrganizationalProviderContributionService $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = new RemoveOrganizationalProviderContributionService($this->contributionRepository, $this->authorizer, $this->locator);
    }

    public function testRemovesContributionAndProviderActivities(): void
    {
        $footprint = $this->propertyFootprint();
        $providerId = ProviderId::generate();
        $contribution = OrganizationalProviderContributionMother::manual(carbonFootprintId: (string) $footprint->id(), providerId: (string) $providerId);
        $this->contributionRepository->add($contribution);
        $this->addProviderActivity($footprint, $providerId, Source::UpstreamTransport, 100.0);
        $this->addOwnerActivity($footprint, Source::UpstreamTransport, 25.0);

        ($this->sut)(new RemoveOrganizationalProviderContributionRequest($contribution->id()), $this->owner());

        $this->assertTrue($contribution->isRemoved());
        $this->assertNull($this->contributionRepository->find($contribution->id()));
        $this->assertCount(0, $footprint->activityIds(new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport, $providerId)));
        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport)));
    }

    public function testOwnerOfAnotherAccountIsDenied(): void
    {
        $footprint = $this->propertyFootprint();
        $contribution = OrganizationalProviderContributionMother::manual(carbonFootprintId: (string) $footprint->id());
        $this->contributionRepository->add($contribution);

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new RemoveOrganizationalProviderContributionRequest($contribution->id()), $this->owner('other-account'));
    }

    public function testCompletedFootprintThrows(): void
    {
        $footprint = $this->completedPropertyFootprint();
        $contribution = OrganizationalProviderContributionMother::manual(carbonFootprintId: (string) $footprint->id());
        $this->contributionRepository->add($contribution);

        $this->expectException(OrganizationalProviderContributionClosedException::class);

        ($this->sut)(new RemoveOrganizationalProviderContributionRequest($contribution->id()), $this->owner());
    }
}
