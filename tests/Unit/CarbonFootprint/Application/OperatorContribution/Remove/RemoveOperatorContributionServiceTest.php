<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OperatorContribution\Remove;

use App\CarbonFootprint\Application\OperatorContribution\Remove\RemoveOperatorContributionRequest;
use App\CarbonFootprint\Application\OperatorContribution\Remove\RemoveOperatorContributionService;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionClosedException;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Tests\Unit\CarbonFootprint\Application\OperatorContribution\OperatorContributionServiceTestCase;

class RemoveOperatorContributionServiceTest extends OperatorContributionServiceTestCase
{
    public function testRemovesTheContributionAndItsActivities(): void
    {
        $footprint = $this->companyFootprint();
        $contribution = $this->contribution($footprint);
        $activityId = $this->addActivity($contribution, Scope::Scope1, Source::FixedFacilities, 1000.0);

        $this->createSUT()(new RemoveOperatorContributionRequest($contribution->id()), $this->owner());

        $this->assertNull($this->contributionRepository->find($contribution->id()));
        $this->assertTrue($contribution->isRemoved());
        $this->assertSame([], $contribution->activityReferences());
        $this->assertNull($this->activityRepository->findById($activityId));
        $this->assertNull($this->contributionRepository->findActiveByCompanyCarbonFootprintAndProperty($footprint->id(), $contribution->propertyId() ?? throw new \LogicException()));
    }

    public function testACompletedFootprintIsClosed(): void
    {
        $contribution = $this->contribution($this->completedCompanyFootprint());

        $this->expectException(OperatorContributionClosedException::class);

        $this->createSUT()(new RemoveOperatorContributionRequest($contribution->id()), $this->owner());
    }

    private function createSUT(): RemoveOperatorContributionService
    {
        return new RemoveOperatorContributionService($this->contributionRepository, $this->companyCarbonFootprintRepository, $this->companyCarbonFootprintAuthorizer, $this->authorizer, $this->activitiesRemover);
    }
}
