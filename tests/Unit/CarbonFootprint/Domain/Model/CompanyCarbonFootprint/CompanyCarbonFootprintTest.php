<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintActivityRemoved;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintCompleted;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMitigationPlanStarted;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRegistered;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintReopened;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintScopeExcluded;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintScopeIncluded;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintSourceExcluded;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintAlreadyCompletedException;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintNotCompletedException;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintNotReadyToCompleteException;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintStatus;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\StageNotExcludableException;
use App\Property\Domain\Model\Property\PropertyId;
use PHPUnit\Framework\TestCase;

final class CompanyCarbonFootprintTest extends TestCase
{
    public function testRegisterPublishesTheEventAndSetsTheState(): void
    {
        $footprint = CompanyCarbonFootprintMother::from('company-1', 'footprint-1', 'account-1', 2024);

        $events = array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof CompanyCarbonFootprintRegistered);
        $this->assertCount(1, $events);
        $this->assertSame('company-1', (string) $footprint->companyId());
        $this->assertSame('account-1', (string) $footprint->accountId());
        $this->assertSame(2024, $footprint->year());
        $this->assertSame(2024, $footprint->calculationYear());
        $this->assertSame(OrganizationalCarbonFootprintStatus::Started, $footprint->status());
    }

    public function testAnswerScopeAdvancesTheStatus(): void
    {
        $footprint = CompanyCarbonFootprintMother::registered();

        $footprint->answerScope(Scope::Scope1);
        $this->assertSame(OrganizationalCarbonFootprintStatus::Scope1Answered, $footprint->status());

        $footprint->answerScope(Scope::Scope2);
        $this->assertSame(OrganizationalCarbonFootprintStatus::Scope2Answered, $footprint->status());
    }

    public function testAnsweringScope1AgainDoesNotGoBack(): void
    {
        $footprint = CompanyCarbonFootprintMother::withScope2Answered('company-1', 'footprint-1');

        $footprint->answerScope(Scope::Scope1);

        $this->assertSame(OrganizationalCarbonFootprintStatus::Scope2Answered, $footprint->status());
    }

    public function testAddActivityIgnoresDuplicates(): void
    {
        $footprint = CompanyCarbonFootprintMother::registered();
        $activityId = ActivityId::generate();
        $placement = new ActivityPlacement(Scope::Scope1, Source::FixedFacilities);

        $footprint->addActivity($activityId, $placement);
        $footprint->addActivity($activityId, $placement);

        $this->assertCount(1, $footprint->activityIds($placement));
    }

    public function testAddActivityIgnoresExcludedSources(): void
    {
        $footprint = CompanyCarbonFootprintMother::registered();
        $placement = new ActivityPlacement(Scope::Scope1, Source::RefrigerantLeaks);
        $footprint->excludeSource(Scope::Scope1, Source::RefrigerantLeaks, ExclusionReason::NoDataAvailable);

        $footprint->addActivity(ActivityId::generate(), $placement);

        $this->assertCount(0, $footprint->activityIds($placement));
    }

    public function testRetainActivitiesOnlyAffectsTheGivenPlacement(): void
    {
        $footprint = CompanyCarbonFootprintMother::registered();
        $keptId = ActivityId::generate();
        $staleId = ActivityId::generate();
        $otherScopeId = ActivityId::generate();

        $scope1 = new ActivityPlacement(Scope::Scope1, Source::FixedFacilities);
        $scope2 = new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity);

        $footprint->addActivity($keptId, $scope1);
        $footprint->addActivity($staleId, $scope1);
        $footprint->addActivity($otherScopeId, $scope2);

        $footprint->retainActivities($scope1, [$keptId]);

        $this->assertCount(1, $footprint->activityIds($scope1));
        $this->assertTrue($footprint->activityIds($scope1)[0]->equals($keptId));
        $this->assertCount(1, $footprint->activityIds($scope2));
    }

    public function testExcludeSourceRemovesItsActivitiesAndPublishesOnce(): void
    {
        $footprint = CompanyCarbonFootprintMother::registered();
        $placement = new ActivityPlacement(Scope::Scope1, Source::FixedFacilities);
        $footprint->addActivity(ActivityId::generate(), $placement);
        $footprint->clearEvents();

        $footprint->excludeSource(Scope::Scope1, Source::FixedFacilities, ExclusionReason::NoDataAvailable, 'no data');
        $footprint->excludeSource(Scope::Scope1, Source::FixedFacilities, ExclusionReason::NoDataAvailable, 'no data');

        $excluded = array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof CompanyCarbonFootprintSourceExcluded);
        $removed = array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof CompanyCarbonFootprintActivityRemoved);

        $this->assertCount(1, $excluded);
        $this->assertCount(1, $removed);
        $this->assertCount(0, $footprint->activityIds($placement));
        $this->assertTrue($footprint->isSourceExcluded(Scope::Scope1, Source::FixedFacilities));
    }

    public function testIncludeSourceRemovesTheExclusion(): void
    {
        $footprint = CompanyCarbonFootprintMother::registered();
        $footprint->excludeSource(Scope::Scope1, Source::FixedFacilities, ExclusionReason::NoDataAvailable);

        $footprint->includeSource(Scope::Scope1, Source::FixedFacilities);

        $this->assertFalse($footprint->isSourceExcluded(Scope::Scope1, Source::FixedFacilities));
        $this->assertCount(0, $footprint->allExclusions());
    }

    public function testExclusionsForIndexesBySource(): void
    {
        $footprint = CompanyCarbonFootprintMother::registered();
        $footprint->excludeSource(Scope::Scope1, Source::OwnVehicles, ExclusionReason::NotApplicable);
        $footprint->excludeSource(Scope::Scope2, Source::BuildingElectricity, ExclusionReason::NotApplicable);

        $exclusions = $footprint->exclusionsFor(Scope::Scope1);

        $this->assertCount(1, $exclusions);
        $this->assertArrayHasKey(Source::OwnVehicles->value, $exclusions);
    }

    public function testRemoveMarksTheFootprintAsRemoved(): void
    {
        $footprint = CompanyCarbonFootprintMother::registered();

        $footprint->remove();

        $this->assertTrue($footprint->isRemoved());
        $this->assertNotNull($footprint->removedAt());
    }

    public function testActivitiesOfDifferentPropertiesAreKeptApart(): void
    {
        $footprint = CompanyCarbonFootprintMother::registered();

        $firstProperty = new ActivityPlacement(Scope::Scope1, Source::FixedFacilities, propertyId: new PropertyId('property-1'));
        $secondProperty = new ActivityPlacement(Scope::Scope1, Source::FixedFacilities, propertyId: new PropertyId('property-2'));
        $unassigned = new ActivityPlacement(Scope::Scope1, Source::FixedFacilities);

        $footprint->addActivity(ActivityId::generate(), $firstProperty);
        $footprint->addActivity(ActivityId::generate(), $secondProperty);
        $footprint->addActivity(ActivityId::generate(), $unassigned);

        $this->assertCount(1, $footprint->activityIds($firstProperty));
        $this->assertCount(1, $footprint->activityIds($secondProperty));
        $this->assertCount(1, $footprint->activityIds($unassigned));

        $footprint->retainActivities($firstProperty, []);

        $this->assertCount(0, $footprint->activityIds($firstProperty));
        $this->assertCount(1, $footprint->activityIds($secondProperty));
        $this->assertCount(1, $footprint->activityIds($unassigned));
    }

    public function testExcludingASourceEmptiesEveryPropertyBlock(): void
    {
        $footprint = CompanyCarbonFootprintMother::registered();

        $firstProperty = new ActivityPlacement(Scope::Scope1, Source::FixedFacilities, propertyId: new PropertyId('property-1'));
        $unassigned = new ActivityPlacement(Scope::Scope1, Source::FixedFacilities);

        $footprint->addActivity(ActivityId::generate(), $firstProperty);
        $footprint->addActivity(ActivityId::generate(), $unassigned);

        $footprint->excludeSource(Scope::Scope1, Source::FixedFacilities, ExclusionReason::NoDataAvailable);

        $this->assertCount(0, $footprint->activityIds($firstProperty));
        $this->assertCount(0, $footprint->activityIds($unassigned));
    }

    public function testCompletedFootprintCannotBeEdited(): void
    {
        $footprint = new CompanyCarbonFootprint(
            id: CompanyCarbonFootprintId::generate(),
            status: OrganizationalCarbonFootprintStatus::Completed,
        );

        $this->expectException(OrganizationalCarbonFootprintAlreadyCompletedException::class);

        $footprint->answerScope(Scope::Scope1);
    }

    public function testCompleteFromScope3AnsweredSetsTheStatusAndTheTotal(): void
    {
        $footprint = CompanyCarbonFootprintMother::withScope3Answered('company-1', 'footprint-1');

        $footprint->complete(new Emissions('3930', '3900', '10', '20'));

        $completed = array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof CompanyCarbonFootprintCompleted);
        $this->assertCount(1, $completed);
        $this->assertSame(OrganizationalCarbonFootprintStatus::Completed, $footprint->status());
        $this->assertTrue($footprint->isCompleted());
        $this->assertSame('3930', $footprint->totalEmissions()->co2eq());
    }

    public function testCompleteFromScope2AnsweredThrows(): void
    {
        $footprint = CompanyCarbonFootprintMother::withScope2Answered('company-1', 'footprint-1');

        $this->expectException(OrganizationalCarbonFootprintNotReadyToCompleteException::class);

        $footprint->complete(new Emissions());
    }

    public function testCompleteFromScope1AnsweredThrows(): void
    {
        $footprint = CompanyCarbonFootprintMother::withScope1Answered('company-1', 'footprint-1');

        $this->expectException(OrganizationalCarbonFootprintNotReadyToCompleteException::class);

        $footprint->complete(new Emissions());
    }

    public function testCompletedFootprintRejectsActivitiesExclusionsAndAnswers(): void
    {
        $footprint = CompanyCarbonFootprintMother::withScope3Answered('company-1', 'footprint-1');
        $footprint->complete(new Emissions());

        $failures = 0;

        foreach ([
            static fn() => $footprint->addActivity(ActivityId::generate(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities)),
            static fn() => $footprint->excludeSource(Scope::Scope1, Source::FixedFacilities, ExclusionReason::NoDataAvailable),
            static fn() => $footprint->answerScope(Scope::Scope2),
        ] as $edition) {
            try {
                $edition();
            } catch (OrganizationalCarbonFootprintAlreadyCompletedException) {
                ++$failures;
            }
        }

        $this->assertSame(3, $failures);
    }

    public function testReopenReturnsToScope3AnsweredWithZeroTotal(): void
    {
        $footprint = CompanyCarbonFootprintMother::withScope3Answered('company-1', 'footprint-1');
        $footprint->complete(new Emissions('3930', '3900', '10', '20'));

        $footprint->reopen();

        $reopened = array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof CompanyCarbonFootprintReopened);
        $this->assertCount(1, $reopened);
        $this->assertSame(OrganizationalCarbonFootprintStatus::Scope3Answered, $footprint->status());
        $this->assertFalse($footprint->isCompleted());
        $this->assertSame('0', $footprint->totalEmissions()->co2eq());
    }

    public function testReopenWithoutCompletingThrows(): void
    {
        $footprint = CompanyCarbonFootprintMother::withScope2Answered('company-1', 'footprint-1');

        $this->expectException(OrganizationalCarbonFootprintNotCompletedException::class);

        $footprint->reopen();
    }

    public function testAnsweringScope1DoesNotStartTheMitigationPlan(): void
    {
        $footprint = CompanyCarbonFootprintMother::withScope1Answered('company-1', 'footprint-1');

        $started = array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof CompanyCarbonFootprintMitigationPlanStarted);
        $this->assertCount(0, $started);
        $this->assertFalse($footprint->isMitigationPlanStarted());
    }

    public function testAnsweringScope2DoesNotStartTheMitigationPlan(): void
    {
        $footprint = CompanyCarbonFootprintMother::withScope2Answered('company-1', 'footprint-1');

        $started = array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof CompanyCarbonFootprintMitigationPlanStarted);
        $this->assertCount(0, $started);
        $this->assertFalse($footprint->isMitigationPlanStarted());
    }

    public function testAnsweringScope3ForTheFirstTimeStartsTheMitigationPlan(): void
    {
        $footprint = CompanyCarbonFootprintMother::withScope3Answered('company-1', 'footprint-1');

        $started = array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof CompanyCarbonFootprintMitigationPlanStarted);
        $this->assertCount(1, $started);
        $this->assertTrue($footprint->isMitigationPlanStarted());
    }

    public function testAnsweringScope3AgainDoesNotStartTheMitigationPlanAgain(): void
    {
        $footprint = CompanyCarbonFootprintMother::withScope3Answered('company-1', 'footprint-1');

        $footprint->answerScope(Scope::Scope3);

        $started = array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof CompanyCarbonFootprintMitigationPlanStarted);
        $this->assertCount(1, $started);
    }

    public function testTheMitigationPlanStaysStartedAfterReopening(): void
    {
        $footprint = CompanyCarbonFootprintMother::withMitigationPlanStarted('company-1', 'footprint-1');
        $footprint->complete(new Emissions('100', '100', '0', '0'));

        $footprint->reopen();
        $footprint->answerScope(Scope::Scope3);

        $started = array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof CompanyCarbonFootprintMitigationPlanStarted);
        $this->assertCount(1, $started);
        $this->assertTrue($footprint->isMitigationPlanStarted());
    }

    public function testExcludeStageRemovesItsActivitiesAndSourceExclusions(): void
    {
        $footprint = CompanyCarbonFootprintMother::withScope2Answered('company-1', 'footprint-1');
        $purchases = new ActivityPlacement(Scope::Scope3, Source::PurchasedGoodsAndServices);
        $electricity = new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity);
        $footprint->addActivity(ActivityId::generate(), $purchases);
        $footprint->addActivity(ActivityId::generate(), $electricity);
        $footprint->excludeSource(Scope::Scope3, Source::Investments, ExclusionReason::NotApplicable);
        $footprint->clearEvents();

        $footprint->excludeStage(Scope::Scope3, ExclusionReason::NoDataAvailable, 'no data');

        $excluded = array_values(array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof CompanyCarbonFootprintScopeExcluded));
        $this->assertCount(1, $excluded);
        $this->assertSame(Scope::Scope3, $excluded[0]->scope());
        $this->assertSame(ExclusionReason::NoDataAvailable, $excluded[0]->reason());
        $this->assertSame('no data', $excluded[0]->comment());
        $this->assertTrue($footprint->isStageExcluded(Scope::Scope3));
        $this->assertSame(ExclusionReason::NoDataAvailable, $footprint->stageExclusionReason(Scope::Scope3));
        $this->assertSame('no data', $footprint->stageExclusionComment(Scope::Scope3));
        $this->assertCount(0, $footprint->activityIds($purchases));
        $this->assertCount(1, $footprint->activityIds($electricity));
        $this->assertSame([], $footprint->exclusionsFor(Scope::Scope3));
    }

    public function testExcludeStageWithTheSameReasonAndCommentIsIdempotent(): void
    {
        $footprint = CompanyCarbonFootprintMother::withScope2Answered('company-1', 'footprint-1');
        $footprint->excludeStage(Scope::Scope3, ExclusionReason::NoDataAvailable, 'no data');
        $footprint->clearEvents();

        $footprint->excludeStage(Scope::Scope3, ExclusionReason::NoDataAvailable, 'no data');

        $this->assertSame([], $footprint->recordedEvents());
    }

    public function testIncludeStageClearsTheStageExclusion(): void
    {
        $footprint = CompanyCarbonFootprintMother::withScope2Answered('company-1', 'footprint-1');
        $footprint->excludeStage(Scope::Scope3, ExclusionReason::NoDataAvailable, 'no data');
        $footprint->clearEvents();

        $footprint->includeStage(Scope::Scope3);
        $footprint->includeStage(Scope::Scope3);

        $included = array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof CompanyCarbonFootprintScopeIncluded);
        $this->assertCount(1, $included);
        $this->assertFalse($footprint->isStageExcluded(Scope::Scope3));
        $this->assertNull($footprint->stageExclusionReason(Scope::Scope3));
        $this->assertNull($footprint->stageExclusionComment(Scope::Scope3));
    }

    public function testScope1CannotBeExcluded(): void
    {
        $footprint = CompanyCarbonFootprintMother::registered();

        $this->expectException(StageNotExcludableException::class);

        $footprint->excludeStage(Scope::Scope1, ExclusionReason::NoDataAvailable);
    }

    public function testAddActivityAndExcludeSourceAreIgnoredInAnExcludedStage(): void
    {
        $footprint = CompanyCarbonFootprintMother::withScope2Answered('company-1', 'footprint-1');
        $footprint->excludeStage(Scope::Scope3, ExclusionReason::NoDataAvailable);
        $placement = new ActivityPlacement(Scope::Scope3, Source::PurchasedGoodsAndServices);

        $footprint->addActivity(ActivityId::generate(), $placement);
        $footprint->excludeSource(Scope::Scope3, Source::Investments, ExclusionReason::NotApplicable);

        $this->assertCount(0, $footprint->activityIds($placement));
        $this->assertSame([], $footprint->exclusionsFor(Scope::Scope3));
    }

    public function testExcludeStageEmptiesEveryPropertyBlock(): void
    {
        $footprint = CompanyCarbonFootprintMother::withScope2Answered('company-1', 'footprint-1');
        $firstProperty = new ActivityPlacement(Scope::Scope3, Source::PurchasedGoodsAndServices, propertyId: new PropertyId('property-1'));
        $unassigned = new ActivityPlacement(Scope::Scope3, Source::Investments);
        $footprint->addActivity(ActivityId::generate(), $firstProperty);
        $footprint->addActivity(ActivityId::generate(), $unassigned);

        $footprint->excludeStage(Scope::Scope3, ExclusionReason::NoDataAvailable);

        $this->assertCount(0, $footprint->activityIds($firstProperty));
        $this->assertCount(0, $footprint->activityIds($unassigned));
    }
}
