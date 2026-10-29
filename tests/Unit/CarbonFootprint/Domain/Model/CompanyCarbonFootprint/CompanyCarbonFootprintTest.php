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
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintEmployeesAnswered;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMitigationPlanStarted;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintNonConsolidatedProperty;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintNonConsolidatedPropertyRecorded;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintNonConsolidatedPropertyRemoved;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintProvidersAnswered;
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
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\EventOperations\Domain\Model\Provider\ProviderId;
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

    public function testChangingTheReasonOfAnExclusionKeepsTheSameRow(): void
    {
        $footprint = CompanyCarbonFootprintMother::registered();
        $footprint->excludeSource(Scope::Scope1, Source::FixedFacilities, ExclusionReason::NoDataAvailable);
        $exclusion = $footprint->exclusionFor(Scope::Scope1, Source::FixedFacilities);
        $this->assertNotNull($exclusion);

        $footprint->excludeSource(Scope::Scope1, Source::FixedFacilities, ExclusionReason::NotApplicable, 'comment');

        $this->assertCount(1, $footprint->allExclusions());
        $this->assertSame($exclusion, $footprint->exclusionFor(Scope::Scope1, Source::FixedFacilities));
        $this->assertSame(ExclusionReason::NotApplicable, $exclusion->reason());
        $this->assertSame('comment', $exclusion->comment());
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

    public function testAFootprintWithEmployeesAnsweredCanBeCompletedWithoutRecordingEvents(): void
    {
        $footprint = CompanyCarbonFootprintMother::withEmployeesAnswered('company-1', 'footprint-1');
        $recordedEvents = count($footprint->recordedEvents());

        $footprint->ensureCanBeCompleted();

        $this->assertCount($recordedEvents, $footprint->recordedEvents());
        $this->assertSame(OrganizationalCarbonFootprintStatus::EmployeesAnswered, $footprint->status());
    }

    public function testAFootprintWithProvidersAnsweredCannotBeCompleted(): void
    {
        $footprint = CompanyCarbonFootprintMother::withProvidersAnswered('company-1', 'footprint-1');

        $this->expectException(OrganizationalCarbonFootprintNotReadyToCompleteException::class);

        $footprint->ensureCanBeCompleted();
    }

    public function testACompletedFootprintCannotBeCompletedAgain(): void
    {
        $footprint = CompanyCarbonFootprintMother::withEmployeesAnswered('company-1', 'footprint-1');
        $footprint->complete(new Emissions());

        $this->expectException(OrganizationalCarbonFootprintAlreadyCompletedException::class);

        $footprint->ensureCanBeCompleted();
    }

    public function testCompleteFromEmployeesAnsweredSetsTheStatusAndTheTotal(): void
    {
        $footprint = CompanyCarbonFootprintMother::withEmployeesAnswered('company-1', 'footprint-1');

        $footprint->complete(new Emissions('3930', '3900', '10', '20'));

        $completed = array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof CompanyCarbonFootprintCompleted);
        $this->assertCount(1, $completed);
        $this->assertSame(OrganizationalCarbonFootprintStatus::Completed, $footprint->status());
        $this->assertTrue($footprint->isCompleted());
        $this->assertSame('3930', $footprint->totalEmissions()->co2eq());
    }

    public function testCompleteFromScope3AnsweredThrows(): void
    {
        $footprint = CompanyCarbonFootprintMother::withScope3Answered('company-1', 'footprint-1');

        $this->expectException(OrganizationalCarbonFootprintNotReadyToCompleteException::class);

        $footprint->complete(new Emissions());
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
        $footprint = CompanyCarbonFootprintMother::withEmployeesAnswered('company-1', 'footprint-1');
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

    public function testReopenReturnsToEmployeesAnsweredWithZeroTotal(): void
    {
        $footprint = CompanyCarbonFootprintMother::withEmployeesAnswered('company-1', 'footprint-1');
        $footprint->complete(new Emissions('3930', '3900', '10', '20'));

        $footprint->reopen();

        $reopened = array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof CompanyCarbonFootprintReopened);
        $this->assertCount(1, $reopened);
        $this->assertSame(OrganizationalCarbonFootprintStatus::EmployeesAnswered, $footprint->status());
        $this->assertFalse($footprint->isCompleted());
        $this->assertSame('0', $footprint->totalEmissions()->co2eq());
    }

    public function testReopenWithoutCompletingThrows(): void
    {
        $footprint = CompanyCarbonFootprintMother::withScope2Answered('company-1', 'footprint-1');

        $this->expectException(OrganizationalCarbonFootprintNotCompletedException::class);

        $footprint->reopen();
    }

    public function testAnswerProvidersAdvancesTheStatus(): void
    {
        $footprint = CompanyCarbonFootprintMother::withScope3Answered('company-1', 'footprint-1');

        $footprint->answerProviders();

        $answered = array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof CompanyCarbonFootprintProvidersAnswered);
        $this->assertCount(1, $answered);
        $this->assertSame(OrganizationalCarbonFootprintStatus::ProvidersAnswered, $footprint->status());
    }

    public function testAnswerProvidersIsIdempotent(): void
    {
        $footprint = CompanyCarbonFootprintMother::withEmployeesAnswered('company-1', 'footprint-1');

        $footprint->answerProviders();

        $answered = array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof CompanyCarbonFootprintProvidersAnswered);
        $this->assertCount(1, $answered);
        $this->assertSame(OrganizationalCarbonFootprintStatus::EmployeesAnswered, $footprint->status());
    }

    public function testAnsweringAnExcludedScope3AlsoAnswersTheProvidersAndTheEmployees(): void
    {
        $footprint = CompanyCarbonFootprintMother::withScope3Excluded('company-1', 'footprint-1');

        $this->assertTrue($footprint->scope3Excluded());
        $this->assertSame(OrganizationalCarbonFootprintStatus::EmployeesAnswered, $footprint->status());
    }

    public function testAnsweringAnIncludedScope3DoesNotAnswerTheProviders(): void
    {
        $footprint = CompanyCarbonFootprintMother::withScope3Answered('company-1', 'footprint-1');

        $this->assertFalse($footprint->scope3Excluded());
        $this->assertSame(OrganizationalCarbonFootprintStatus::Scope3Answered, $footprint->status());
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

    public function testRecordNonConsolidatedPropertyPublishesTheEvent(): void
    {
        $footprint = CompanyCarbonFootprintMother::withScope3Answered('company-1', 'footprint-1');
        $footprint->clearEvents();
        $origin = PropertyCarbonFootprintId::generate();

        $footprint->recordNonConsolidatedProperty(new PropertyId('property-1'), Source::Franchises, $origin, new Emissions('5000', '4900', '1', '1'), null);

        $recorded = array_values(array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof CompanyCarbonFootprintNonConsolidatedPropertyRecorded));
        $this->assertCount(1, $recorded);
        $this->assertSame('property-1', (string) $recorded[0]->propertyId());
        $this->assertSame(Source::Franchises, $recorded[0]->source());

        $lines = $footprint->nonConsolidatedPropertyLines();
        $this->assertCount(1, $lines);
        $this->assertSame('5000', $lines[0]->emissions->co2eq());
        $this->assertSame((string) $origin, (string) $lines[0]->propertyCarbonFootprintId);
    }

    public function testRecordNonConsolidatedPropertyIsIdempotent(): void
    {
        $footprint = CompanyCarbonFootprintMother::withScope3Answered('company-1', 'footprint-1');
        $origin = PropertyCarbonFootprintId::generate();
        $footprint->recordNonConsolidatedProperty(new PropertyId('property-1'), Source::Franchises, $origin, new Emissions('5000'), '10');
        $footprint->clearEvents();

        $footprint->recordNonConsolidatedProperty(new PropertyId('property-1'), Source::Franchises, new PropertyCarbonFootprintId((string) $origin), new Emissions('5000'), '10');

        $this->assertSame([], $footprint->recordedEvents());
    }

    public function testRecordNonConsolidatedPropertyReplacesTheRowOfTheSameProperty(): void
    {
        $footprint = CompanyCarbonFootprintMother::withNonConsolidatedProperty(new PropertyId('property-1'), Source::Franchises, new Emissions('5000'));

        $footprint->recordNonConsolidatedProperty(new PropertyId('property-1'), Source::DownstreamLeasedAssets, null, new Emissions('300'), null);

        $lines = $footprint->nonConsolidatedPropertyLines();
        $this->assertCount(1, $lines);
        $this->assertSame(Source::DownstreamLeasedAssets, $lines[0]->source);
        $this->assertSame('300', $lines[0]->emissions->co2eq());
    }

    public function testRecordNonConsolidatedPropertyKeepsTheIdOfTheReplacedRow(): void
    {
        $footprint = CompanyCarbonFootprintMother::withNonConsolidatedProperty(new PropertyId('property-1'), Source::Franchises, new Emissions('5000'));
        $before = $this->nonConsolidatedPropertyIds($footprint);

        $footprint->recordNonConsolidatedProperty(new PropertyId('property-1'), Source::Franchises, null, new Emissions('5000'), '4500');

        $this->assertCount(1, $before);
        $this->assertSame($before, $this->nonConsolidatedPropertyIds($footprint));
        $this->assertSame('4500', $footprint->nonConsolidatedPropertyLines()[0]->emissions->co2eq());
    }

    public function testRecordNonConsolidatedPropertyRejectsOtherSources(): void
    {
        $footprint = CompanyCarbonFootprintMother::withScope3Answered('company-1', 'footprint-1');

        $this->expectException(\InvalidArgumentException::class);

        $footprint->recordNonConsolidatedProperty(new PropertyId('property-1'), Source::Investments, null, new Emissions(), null);
    }

    public function testRecordNonConsolidatedPropertyRejectsNegativeManualEmissions(): void
    {
        $footprint = CompanyCarbonFootprintMother::withScope3Answered('company-1', 'footprint-1');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Manual emissions must be a non-negative number');

        $footprint->recordNonConsolidatedProperty(new PropertyId('property-1'), Source::Franchises, null, new Emissions(), '-1');
    }

    public function testRecordNonConsolidatedPropertyRejectsNonNumericManualEmissions(): void
    {
        $footprint = CompanyCarbonFootprintMother::withScope3Answered('company-1', 'footprint-1');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Manual emissions must be a non-negative number');

        $footprint->recordNonConsolidatedProperty(new PropertyId('property-1'), Source::Franchises, null, new Emissions(), 'abc');
    }

    public function testRecordNonConsolidatedPropertyWithExcludedSourceRemovesTheRow(): void
    {
        $footprint = CompanyCarbonFootprintMother::withNonConsolidatedProperty(new PropertyId('property-1'), Source::Franchises, new Emissions('5000'));
        $footprint->excludeSource(Scope::Scope3, Source::Franchises, ExclusionReason::NotApplicable);
        $footprint->recordNonConsolidatedProperty(new PropertyId('property-2'), Source::Franchises, null, new Emissions('10'), null);

        $this->assertSame([], $footprint->nonConsolidatedPropertyLines());
    }

    public function testRecordNonConsolidatedPropertyWithExcludedStageRemovesTheExistingRow(): void
    {
        $footprint = CompanyCarbonFootprintMother::withNonConsolidatedProperty(new PropertyId('property-1'), Source::DownstreamLeasedAssets, new Emissions('5000'));
        $footprint->excludeStage(Scope::Scope3, ExclusionReason::NoDataAvailable);

        $footprint->recordNonConsolidatedProperty(new PropertyId('property-1'), Source::DownstreamLeasedAssets, null, new Emissions('5000'), null);

        $this->assertSame([], $footprint->nonConsolidatedPropertyLines());
    }

    public function testRetainNonConsolidatedPropertiesRemovesTheOthers(): void
    {
        $footprint = CompanyCarbonFootprintMother::withNonConsolidatedProperty(new PropertyId('property-1'), Source::Franchises, new Emissions('5000'));
        $footprint->recordNonConsolidatedProperty(new PropertyId('property-2'), Source::DownstreamLeasedAssets, null, new Emissions('100'), null);
        $footprint->clearEvents();

        $footprint->retainNonConsolidatedProperties([new PropertyId('property-2')]);

        $removed = array_values(array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof CompanyCarbonFootprintNonConsolidatedPropertyRemoved));
        $this->assertCount(1, $removed);
        $this->assertSame('property-1', (string) $removed[0]->propertyId());
        $this->assertCount(1, $footprint->nonConsolidatedPropertyLines());
        $this->assertSame('property-2', (string) $footprint->nonConsolidatedPropertyLines()[0]->propertyId);
    }

    public function testExcludeFranchisesRemovesOnlyItsRows(): void
    {
        $footprint = CompanyCarbonFootprintMother::withNonConsolidatedProperty(new PropertyId('property-1'), Source::Franchises, new Emissions('5000'));
        $footprint->recordNonConsolidatedProperty(new PropertyId('property-2'), Source::DownstreamLeasedAssets, null, new Emissions('100'), null);

        $footprint->excludeSource(Scope::Scope3, Source::Franchises, ExclusionReason::NotApplicable);

        $lines = $footprint->nonConsolidatedPropertyLines();
        $this->assertCount(1, $lines);
        $this->assertSame(Source::DownstreamLeasedAssets, $lines[0]->source);
    }

    public function testExcludeScope3RemovesEveryRow(): void
    {
        $footprint = CompanyCarbonFootprintMother::withNonConsolidatedProperty(new PropertyId('property-1'), Source::Franchises, new Emissions('5000'));
        $footprint->recordNonConsolidatedProperty(new PropertyId('property-2'), Source::DownstreamLeasedAssets, null, new Emissions('100'), null);

        $footprint->excludeStage(Scope::Scope3, ExclusionReason::NoDataAvailable);

        $this->assertSame([], $footprint->nonConsolidatedPropertyLines());
    }

    public function testCompletedFootprintCannotRecordNonConsolidatedProperties(): void
    {
        $footprint = CompanyCarbonFootprintMother::withEmployeesAnswered('company-1', 'footprint-1');
        $footprint->complete(new Emissions('1'));

        $this->expectException(OrganizationalCarbonFootprintAlreadyCompletedException::class);

        $footprint->recordNonConsolidatedProperty(new PropertyId('property-1'), Source::Franchises, null, new Emissions(), null);
    }

    public function testNonConsolidatedPropertyLinesApplyTheManualEmissions(): void
    {
        $footprint = CompanyCarbonFootprintMother::withNonConsolidatedProperty(new PropertyId('property-1'), Source::DownstreamLeasedAssets, new Emissions('0'), '4500');

        $line = $footprint->nonConsolidatedPropertyLines()[0];

        $this->assertSame('4500', $line->emissions->co2eq());
        $this->assertSame('0', $line->emissions->co2());
        $this->assertSame('0', $line->calculatedEmissions->co2eq());
        $this->assertSame('4500', $line->manualCo2eq);
    }

    /** @return list<string> */
    private function nonConsolidatedPropertyIds(CompanyCarbonFootprint $footprint): array
    {
        /** @var iterable<CompanyCarbonFootprintNonConsolidatedProperty> $rows */
        $rows = new \ReflectionProperty(CompanyCarbonFootprint::class, 'nonConsolidatedProperties')->getValue($footprint);

        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (string) $row->id();
        }

        return $ids;
    }

    public function testAddActivityWithProviderAndPropertyIsOnlyRecoveredWithTheSamePlacement(): void
    {
        $footprint = CompanyCarbonFootprintMother::withScope2Answered('company-1', 'footprint-1');
        $providerId = ProviderId::generate();
        $propertyId = new PropertyId('property-1');
        $placement = new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport, providerId: $providerId, propertyId: $propertyId);

        $footprint->addActivity(ActivityId::generate(), $placement);

        $this->assertCount(1, $footprint->activityIds($placement));
        $this->assertCount(0, $footprint->activityIds(new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport, providerId: $providerId)));
        $this->assertCount(0, $footprint->activityIds(new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport, propertyId: $propertyId)));
    }

    public function testExcludeSourceAndStageAlsoRemoveProviderActivities(): void
    {
        $footprint = CompanyCarbonFootprintMother::withScope2Answered('company-1', 'footprint-1');
        $transport = new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport, providerId: ProviderId::generate());
        $waste = new ActivityPlacement(Scope::Scope3, Source::OperationsWaste, providerId: ProviderId::generate());
        $footprint->addActivity(ActivityId::generate(), $transport);
        $footprint->addActivity(ActivityId::generate(), $waste);

        $footprint->excludeSource(Scope::Scope3, Source::UpstreamTransport, ExclusionReason::NoDataAvailable);

        $this->assertCount(0, $footprint->activityIds($transport));
        $this->assertCount(1, $footprint->activityIds($waste));

        $footprint->excludeStage(Scope::Scope3, ExclusionReason::NoDataAvailable);

        $this->assertCount(0, $footprint->activityIds($waste));
    }

    public function testRemoveActivitiesOfProviderKeepsOwnerActivities(): void
    {
        $footprint = CompanyCarbonFootprintMother::withScope2Answered('company-1', 'footprint-1');
        $providerId = ProviderId::generate();
        $ownerPlacement = new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport);
        $providerPlacement = new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport, providerId: $providerId, propertyId: new PropertyId('property-1'));
        $footprint->addActivity(ActivityId::generate(), $ownerPlacement);
        $footprint->addActivity(ActivityId::generate(), $providerPlacement);

        $footprint->removeActivitiesOfProvider($providerId);

        $this->assertCount(1, $footprint->activityIds($ownerPlacement));
        $this->assertCount(0, $footprint->activityIds($providerPlacement));
    }

    public function testCompleteFromProvidersAnsweredThrows(): void
    {
        $footprint = CompanyCarbonFootprintMother::withProvidersAnswered('company-1', 'footprint-1');

        $this->expectException(OrganizationalCarbonFootprintNotReadyToCompleteException::class);

        $footprint->complete(new Emissions());
    }

    public function testAnswerEmployeesAdvancesTheStatus(): void
    {
        $footprint = CompanyCarbonFootprintMother::withProvidersAnswered('company-1', 'footprint-1');

        $footprint->answerEmployees();

        $answered = array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof CompanyCarbonFootprintEmployeesAnswered);
        $this->assertCount(1, $answered);
        $this->assertSame(OrganizationalCarbonFootprintStatus::EmployeesAnswered, $footprint->status());
    }

    public function testAnswerEmployeesIsIdempotent(): void
    {
        $footprint = CompanyCarbonFootprintMother::withEmployeesAnswered('company-1', 'footprint-1');

        $footprint->answerEmployees();

        $answered = array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof CompanyCarbonFootprintEmployeesAnswered);
        $this->assertCount(1, $answered);
        $this->assertSame(OrganizationalCarbonFootprintStatus::EmployeesAnswered, $footprint->status());
    }

    public function testAnswerEmployeesOnACompletedFootprintThrows(): void
    {
        $footprint = CompanyCarbonFootprintMother::withEmployeesAnswered('company-1', 'footprint-1');
        $footprint->complete(new Emissions());

        $this->expectException(OrganizationalCarbonFootprintAlreadyCompletedException::class);

        $footprint->answerEmployees();
    }

    public function testAddActivityFromEmployeesIsOnlyRecoveredWithTheSamePlacementAndProperty(): void
    {
        $footprint = CompanyCarbonFootprintMother::registered();
        $propertyId = PropertyId::generate();
        $ownerPlacement = new ActivityPlacement(Scope::Scope3, Source::EmployeeCommuting, propertyId: $propertyId);
        $employeePlacement = new ActivityPlacement(Scope::Scope3, Source::EmployeeCommuting, fromEmployees: true, propertyId: $propertyId);
        $unassignedEmployeePlacement = new ActivityPlacement(Scope::Scope3, Source::EmployeeCommuting, fromEmployees: true);
        $activityId = ActivityId::generate();

        $footprint->addActivity($activityId, $employeePlacement);

        $this->assertCount(0, $footprint->activityIds($ownerPlacement));
        $this->assertCount(0, $footprint->activityIds($unassignedEmployeePlacement));
        $this->assertCount(0, $footprint->activityIds(new ActivityPlacement(Scope::Scope3, Source::EmployeeCommuting, fromEmployees: true, propertyId: PropertyId::generate())));
        $this->assertCount(1, $footprint->activityIds($employeePlacement));
        $this->assertTrue($activityId->equals($footprint->activityIds($employeePlacement)[0]));
    }

    public function testRemoveEmployeeActivitiesKeepsTheOthers(): void
    {
        $footprint = CompanyCarbonFootprintMother::withScope2Answered('company-1', 'footprint-1');
        $ownerPlacement = new ActivityPlacement(Scope::Scope3, Source::EmployeeCommuting);
        $providerPlacement = new ActivityPlacement(Scope::Scope3, Source::EmployeeCommuting, providerId: ProviderId::generate());
        $commutingPlacement = new ActivityPlacement(Scope::Scope3, Source::EmployeeCommuting, fromEmployees: true);
        $travelPlacement = new ActivityPlacement(Scope::Scope3, Source::BusinessTravel, fromEmployees: true);
        $footprint->addActivity(ActivityId::generate(), $ownerPlacement);
        $footprint->addActivity(ActivityId::generate(), $providerPlacement);
        $footprint->addActivity(ActivityId::generate(), $commutingPlacement);
        $footprint->addActivity(ActivityId::generate(), $travelPlacement);

        $footprint->removeEmployeeActivities();

        $this->assertCount(1, $footprint->activityIds($ownerPlacement));
        $this->assertCount(1, $footprint->activityIds($providerPlacement));
        $this->assertCount(0, $footprint->activityIds($commutingPlacement));
        $this->assertCount(0, $footprint->activityIds($travelPlacement));
    }

    public function testExcludeSourceAndStageAlsoRemoveEmployeeActivities(): void
    {
        $footprint = CompanyCarbonFootprintMother::withScope2Answered('company-1', 'footprint-1');
        $commutingPlacement = new ActivityPlacement(Scope::Scope3, Source::EmployeeCommuting, fromEmployees: true);
        $travelPlacement = new ActivityPlacement(Scope::Scope3, Source::BusinessTravel, fromEmployees: true);
        $footprint->addActivity(ActivityId::generate(), $commutingPlacement);
        $footprint->addActivity(ActivityId::generate(), $travelPlacement);

        $footprint->excludeSource(Scope::Scope3, Source::EmployeeCommuting, ExclusionReason::NoDataAvailable);

        $this->assertCount(0, $footprint->activityIds($commutingPlacement));
        $this->assertCount(1, $footprint->activityIds($travelPlacement));

        $footprint->excludeStage(Scope::Scope3, ExclusionReason::NoDataAvailable);

        $this->assertCount(0, $footprint->activityIds($travelPlacement));
    }
}
