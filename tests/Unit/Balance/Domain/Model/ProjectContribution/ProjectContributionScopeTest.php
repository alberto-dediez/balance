<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\ProjectContribution;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionScope;
use App\Security\Domain\Model\Account\AccountId;
use PHPUnit\Framework\TestCase;

class ProjectContributionScopeTest extends TestCase
{
    public function testAccountScopeIncludesEveryOriginOfTheAccount(): void
    {
        $accountId = AccountId::generate();
        $scope = ProjectContributionScope::forAccount($accountId);

        $this->assertTrue($scope->includes(ProjectContributionMother::pending($accountId, origin: ProjectContributionOrigin::EventCarbonFootprint)));
        $this->assertTrue($scope->includes(ProjectContributionMother::pending($accountId)));
        $this->assertFalse($scope->includes(ProjectContributionMother::pending()));
    }

    public function testPropertyAccountScopeOnlyIncludesPropertyOrigins(): void
    {
        $accountId = AccountId::generate();
        $scope = new ProjectContributionScope(propertyAccountIds: [$accountId]);

        $this->assertTrue($scope->includes(ProjectContributionMother::pending($accountId, origin: ProjectContributionOrigin::Property)));
        $this->assertTrue($scope->includes(ProjectContributionMother::pending($accountId, origin: ProjectContributionOrigin::PropertyTripCalculation)));
        $this->assertFalse($scope->includes(ProjectContributionMother::pending($accountId, origin: ProjectContributionOrigin::TripCalculation)));
        $this->assertFalse($scope->includes(ProjectContributionMother::pending($accountId, origin: ProjectContributionOrigin::AttendeeSurveyResponse)));
    }

    public function testOriginIdsAreMatchedAgainstTheirOrigin(): void
    {
        $scope = new ProjectContributionScope(propertyIds: ['property'], propertyTripCalculationIds: ['calculation']);

        $this->assertTrue($scope->includes(ProjectContributionMother::pending(originId: 'property', origin: ProjectContributionOrigin::Property)));
        $this->assertTrue($scope->includes(ProjectContributionMother::pending(originId: 'calculation')));
        $this->assertFalse($scope->includes(ProjectContributionMother::pending(originId: 'calculation', origin: ProjectContributionOrigin::Property)));
        $this->assertFalse($scope->includes(ProjectContributionMother::pending(originId: 'property')));
    }

    public function testMergeCombinesWithoutDuplicates(): void
    {
        $accountId = AccountId::generate();

        $scope = ProjectContributionScope::forAccount($accountId)
            ->merge(ProjectContributionScope::forAccount($accountId))
            ->merge(new ProjectContributionScope(propertyIds: ['a']))
            ->merge(new ProjectContributionScope(propertyIds: ['a', 'b']));

        $this->assertCount(1, $scope->accountIds);
        $this->assertSame(['a', 'b'], $scope->propertyIds);
        $this->assertFalse($scope->isEmpty());
        $this->assertTrue((new ProjectContributionScope())->isEmpty());
    }
}
