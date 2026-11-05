<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityOverrideJustificationRequiredException;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityVerdict;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityDenominators;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintAlreadyCompletedException;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintNotCompletedException;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintNotReadyToCompleteException;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintScope2NotAnsweredException;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintStatus;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\StageNotExcludableException;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintActivityRemoved;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintCompleted;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintEmployeesAnswered;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintIntensityDenominatorsDeclared;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMarkedAsPrecalculated;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMaterialityAnswered;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMaterialityDecisionsApplied;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMaterialityDecisionsCleared;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMaterialityOverridden;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMaterialityOverrideReverted;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMitigationPlanStarted;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintProvidersAnswered;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRegistered;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintReopened;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintScopeExcluded;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintScopeIncluded;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintSourceExcluded;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintUnmarkedAsPrecalculated;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Security\Domain\Model\User\UserId;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityDecisionInputMother;
use PHPUnit\Framework\TestCase;

final class PropertyCarbonFootprintTest extends TestCase
{
    public function testRegisterPublishesTheEventAndSetsTheState(): void
    {
        $footprint = PropertyCarbonFootprintMother::from('property-1', 'footprint-1', 'account-1', 2024);

        $events = array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof PropertyCarbonFootprintRegistered);
        $this->assertCount(1, $events);
        $this->assertSame('property-1', (string) $footprint->propertyId());
        $this->assertSame('account-1', (string) $footprint->accountId());
        $this->assertSame(2024, $footprint->year());
        $this->assertSame(2024, $footprint->calculationYear());
        $this->assertSame(OrganizationalCarbonFootprintStatus::Started, $footprint->status());
    }

    public function testAnswerScopeAdvancesTheStatus(): void
    {
        $footprint = PropertyCarbonFootprintMother::registered();

        $footprint->answerScope(Scope::Scope1);
        $this->assertSame(OrganizationalCarbonFootprintStatus::Scope1Answered, $footprint->status());

        $footprint->answerScope(Scope::Scope2);
        $this->assertSame(OrganizationalCarbonFootprintStatus::Scope2Answered, $footprint->status());
    }

    public function testAnsweringScope1AgainDoesNotGoBack(): void
    {
        $footprint = PropertyCarbonFootprintMother::withScope2Answered('property-1', 'footprint-1');

        $footprint->answerScope(Scope::Scope1);

        $this->assertSame(OrganizationalCarbonFootprintStatus::Scope2Answered, $footprint->status());
    }

    public function testAddActivityIgnoresDuplicates(): void
    {
        $footprint = PropertyCarbonFootprintMother::registered();
        $activityId = ActivityId::generate();
        $placement = new ActivityPlacement(Scope::Scope1, Source::FixedFacilities);

        $footprint->addActivity($activityId, $placement);
        $footprint->addActivity($activityId, $placement);

        $this->assertCount(1, $footprint->activityIds($placement));
    }

    public function testAddActivityIgnoresExcludedSources(): void
    {
        $footprint = PropertyCarbonFootprintMother::registered();
        $placement = new ActivityPlacement(Scope::Scope1, Source::RefrigerantLeaks);
        $footprint->excludeSource(Scope::Scope1, Source::RefrigerantLeaks, ExclusionReason::NoDataAvailable);

        $footprint->addActivity(ActivityId::generate(), $placement);

        $this->assertCount(0, $footprint->activityIds($placement));
    }

    public function testRetainActivitiesOnlyAffectsTheGivenPlacement(): void
    {
        $footprint = PropertyCarbonFootprintMother::registered();
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
        $footprint = PropertyCarbonFootprintMother::registered();
        $placement = new ActivityPlacement(Scope::Scope1, Source::FixedFacilities);
        $footprint->addActivity(ActivityId::generate(), $placement);
        $footprint->clearEvents();

        $footprint->excludeSource(Scope::Scope1, Source::FixedFacilities, ExclusionReason::NoDataAvailable, 'no data');
        $footprint->excludeSource(Scope::Scope1, Source::FixedFacilities, ExclusionReason::NoDataAvailable, 'no data');

        $excluded = array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof PropertyCarbonFootprintSourceExcluded);
        $removed = array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof PropertyCarbonFootprintActivityRemoved);

        $this->assertCount(1, $excluded);
        $this->assertCount(1, $removed);
        $this->assertCount(0, $footprint->activityIds($placement));
        $this->assertTrue($footprint->isSourceExcluded(Scope::Scope1, Source::FixedFacilities));
    }

    public function testExcludeCompanyOnlySourceIsIgnored(): void
    {
        $footprint = PropertyCarbonFootprintMother::registered();
        $footprint->clearEvents();

        $footprint->excludeSource(Scope::Scope3, Source::Franchises, ExclusionReason::NotApplicable);

        $this->assertSame([], $footprint->recordedEvents());
        $this->assertFalse($footprint->isSourceExcluded(Scope::Scope3, Source::Franchises));
    }

    public function testIncludeSourceRemovesTheExclusion(): void
    {
        $footprint = PropertyCarbonFootprintMother::registered();
        $footprint->excludeSource(Scope::Scope1, Source::FixedFacilities, ExclusionReason::NoDataAvailable);

        $footprint->includeSource(Scope::Scope1, Source::FixedFacilities);

        $this->assertFalse($footprint->isSourceExcluded(Scope::Scope1, Source::FixedFacilities));
        $this->assertCount(0, $footprint->allExclusions());
    }

    public function testChangingTheReasonOfAnExclusionKeepsTheSameRow(): void
    {
        $footprint = PropertyCarbonFootprintMother::registered();
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
        $footprint = PropertyCarbonFootprintMother::registered();
        $footprint->excludeSource(Scope::Scope1, Source::OwnVehicles, ExclusionReason::NotApplicable);
        $footprint->excludeSource(Scope::Scope2, Source::BuildingElectricity, ExclusionReason::NotApplicable);

        $exclusions = $footprint->exclusionsFor(Scope::Scope1);

        $this->assertCount(1, $exclusions);
        $this->assertArrayHasKey(Source::OwnVehicles->value, $exclusions);
    }

    public function testRemoveMarksTheFootprintAsRemoved(): void
    {
        $footprint = PropertyCarbonFootprintMother::registered();

        $footprint->remove();

        $this->assertTrue($footprint->isRemoved());
        $this->assertNotNull($footprint->removedAt());
    }

    public function testCompletedFootprintCannotBeEdited(): void
    {
        $footprint = new PropertyCarbonFootprint(
            id: PropertyCarbonFootprintId::generate(),
            status: OrganizationalCarbonFootprintStatus::Completed,
        );

        $this->expectException(OrganizationalCarbonFootprintAlreadyCompletedException::class);

        $footprint->answerScope(Scope::Scope1);
    }

    public function testAFootprintWithEmployeesAnsweredCanBeCompletedWithoutRecordingEvents(): void
    {
        $footprint = PropertyCarbonFootprintMother::withEmployeesAnswered('property-1', 'footprint-1');
        $recordedEvents = count($footprint->recordedEvents());

        $footprint->ensureCanBeCompleted();

        $this->assertCount($recordedEvents, $footprint->recordedEvents());
        $this->assertSame(OrganizationalCarbonFootprintStatus::EmployeesAnswered, $footprint->status());
    }

    public function testAFootprintWithProvidersAnsweredCannotBeCompleted(): void
    {
        $footprint = PropertyCarbonFootprintMother::withProvidersAnswered('property-1', 'footprint-1');

        $this->expectException(OrganizationalCarbonFootprintNotReadyToCompleteException::class);

        $footprint->ensureCanBeCompleted();
    }

    public function testACompletedFootprintCannotBeCompletedAgain(): void
    {
        $footprint = PropertyCarbonFootprintMother::withEmployeesAnswered('property-1', 'footprint-1');
        $footprint->complete(new Emissions());

        $this->expectException(OrganizationalCarbonFootprintAlreadyCompletedException::class);

        $footprint->ensureCanBeCompleted();
    }

    public function testCompleteFromEmployeesAnsweredSetsTheStatusAndTheTotal(): void
    {
        $footprint = PropertyCarbonFootprintMother::withEmployeesAnswered('property-1', 'footprint-1');

        $footprint->complete(new Emissions('3930', '3900', '10', '20'));

        $completed = array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof PropertyCarbonFootprintCompleted);
        $this->assertCount(1, $completed);
        $this->assertSame(OrganizationalCarbonFootprintStatus::Completed, $footprint->status());
        $this->assertTrue($footprint->isCompleted());
        $this->assertSame('3930', $footprint->totalEmissions()->co2eq());
    }

    public function testCompleteFromScope3AnsweredThrows(): void
    {
        $footprint = PropertyCarbonFootprintMother::withScope3Answered('property-1', 'footprint-1');

        $this->expectException(OrganizationalCarbonFootprintNotReadyToCompleteException::class);

        $footprint->complete(new Emissions());
    }

    public function testCompleteFromScope2AnsweredThrows(): void
    {
        $footprint = PropertyCarbonFootprintMother::withScope2Answered('property-1', 'footprint-1');

        $this->expectException(OrganizationalCarbonFootprintNotReadyToCompleteException::class);

        $footprint->complete(new Emissions());
    }

    public function testCompleteFromScope1AnsweredThrows(): void
    {
        $footprint = PropertyCarbonFootprintMother::withScope1Answered('property-1', 'footprint-1');

        $this->expectException(OrganizationalCarbonFootprintNotReadyToCompleteException::class);

        $footprint->complete(new Emissions());
    }

    public function testCompletedFootprintRejectsActivitiesExclusionsAndAnswers(): void
    {
        $footprint = PropertyCarbonFootprintMother::withEmployeesAnswered('property-1', 'footprint-1');
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
        $footprint = PropertyCarbonFootprintMother::withEmployeesAnswered('property-1', 'footprint-1');
        $footprint->complete(new Emissions('3930', '3900', '10', '20'));

        $footprint->reopen();

        $reopened = array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof PropertyCarbonFootprintReopened);
        $this->assertCount(1, $reopened);
        $this->assertSame(OrganizationalCarbonFootprintStatus::EmployeesAnswered, $footprint->status());
        $this->assertFalse($footprint->isCompleted());
        $this->assertSame('0', $footprint->totalEmissions()->co2eq());
    }

    public function testReopenWithoutCompletingThrows(): void
    {
        $footprint = PropertyCarbonFootprintMother::withScope2Answered('property-1', 'footprint-1');

        $this->expectException(OrganizationalCarbonFootprintNotCompletedException::class);

        $footprint->reopen();
    }

    public function testAnswerMaterialityAdvancesTheStatus(): void
    {
        $footprint = PropertyCarbonFootprintMother::withScope2Answered('property-1', 'footprint-1');

        $footprint->answerMateriality();

        $answered = array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof PropertyCarbonFootprintMaterialityAnswered);
        $this->assertCount(1, $answered);
        $this->assertSame(OrganizationalCarbonFootprintStatus::MaterialityAnswered, $footprint->status());
    }

    public function testAnswerMaterialityDoesNotMoveBackAnAdvancedFootprint(): void
    {
        $footprint = PropertyCarbonFootprintMother::withProvidersAnswered('property-1', 'footprint-1');

        $footprint->answerMateriality();

        $this->assertSame(OrganizationalCarbonFootprintStatus::ProvidersAnswered, $footprint->status());
    }

    public function testAnswerMaterialityOnAnAnsweredFootprintDoesNotPublishTheEventAgain(): void
    {
        $footprint = PropertyCarbonFootprintMother::withMaterialityAnswered('property-1', 'footprint-1');

        $footprint->answerMateriality();

        $answered = array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof PropertyCarbonFootprintMaterialityAnswered);
        $this->assertCount(1, $answered);
        $this->assertSame(OrganizationalCarbonFootprintStatus::MaterialityAnswered, $footprint->status());
    }

    public function testAnswerMaterialityWithoutScope2AnsweredThrows(): void
    {
        $footprint = PropertyCarbonFootprintMother::withScope1Answered('property-1', 'footprint-1');

        $this->expectException(OrganizationalCarbonFootprintScope2NotAnsweredException::class);

        $footprint->answerMateriality();
    }

    public function testAnswerMaterialityOnACompletedFootprintThrows(): void
    {
        $footprint = PropertyCarbonFootprintMother::withEmployeesAnswered('property-1', 'footprint-1');
        $footprint->complete(new Emissions());

        $this->expectException(OrganizationalCarbonFootprintAlreadyCompletedException::class);

        $footprint->answerMateriality();
    }

    public function testAnswerProvidersAdvancesTheStatus(): void
    {
        $footprint = PropertyCarbonFootprintMother::withScope3Answered('property-1', 'footprint-1');

        $footprint->answerProviders();

        $answered = array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof PropertyCarbonFootprintProvidersAnswered);
        $this->assertCount(1, $answered);
        $this->assertSame(OrganizationalCarbonFootprintStatus::ProvidersAnswered, $footprint->status());
    }

    public function testAnswerProvidersIsIdempotent(): void
    {
        $footprint = PropertyCarbonFootprintMother::withEmployeesAnswered('property-1', 'footprint-1');

        $footprint->answerProviders();

        $answered = array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof PropertyCarbonFootprintProvidersAnswered);
        $this->assertCount(1, $answered);
        $this->assertSame(OrganizationalCarbonFootprintStatus::EmployeesAnswered, $footprint->status());
    }

    public function testAnsweringAnExcludedScope3AlsoAnswersTheProvidersAndTheEmployees(): void
    {
        $footprint = PropertyCarbonFootprintMother::withScope3Excluded('property-1', 'footprint-1');

        $this->assertTrue($footprint->scope3Excluded());
        $this->assertSame(OrganizationalCarbonFootprintStatus::EmployeesAnswered, $footprint->status());
    }

    public function testAnsweringAnIncludedScope3DoesNotAnswerTheProviders(): void
    {
        $footprint = PropertyCarbonFootprintMother::withScope3Answered('property-1', 'footprint-1');

        $this->assertFalse($footprint->scope3Excluded());
        $this->assertSame(OrganizationalCarbonFootprintStatus::Scope3Answered, $footprint->status());
    }

    public function testAnsweringScope1DoesNotStartTheMitigationPlan(): void
    {
        $footprint = PropertyCarbonFootprintMother::withScope1Answered('property-1', 'footprint-1');

        $started = array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof PropertyCarbonFootprintMitigationPlanStarted);
        $this->assertCount(0, $started);
        $this->assertFalse($footprint->isMitigationPlanStarted());
    }

    public function testAnsweringScope2DoesNotStartTheMitigationPlan(): void
    {
        $footprint = PropertyCarbonFootprintMother::withScope2Answered('property-1', 'footprint-1');

        $started = array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof PropertyCarbonFootprintMitigationPlanStarted);
        $this->assertCount(0, $started);
        $this->assertFalse($footprint->isMitigationPlanStarted());
    }

    public function testAnsweringScope3ForTheFirstTimeStartsTheMitigationPlan(): void
    {
        $footprint = PropertyCarbonFootprintMother::withScope3Answered('property-1', 'footprint-1');

        $started = array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof PropertyCarbonFootprintMitigationPlanStarted);
        $this->assertCount(1, $started);
        $this->assertTrue($footprint->isMitigationPlanStarted());
    }

    public function testAnsweringScope3AgainDoesNotStartTheMitigationPlanAgain(): void
    {
        $footprint = PropertyCarbonFootprintMother::withScope3Answered('property-1', 'footprint-1');

        $footprint->answerScope(Scope::Scope3);

        $started = array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof PropertyCarbonFootprintMitigationPlanStarted);
        $this->assertCount(1, $started);
    }

    public function testTheMitigationPlanStaysStartedAfterReopening(): void
    {
        $footprint = PropertyCarbonFootprintMother::withMitigationPlanStarted('property-1', 'footprint-1');
        $footprint->complete(new Emissions('100', '100', '0', '0'));

        $footprint->reopen();
        $footprint->answerScope(Scope::Scope3);

        $started = array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof PropertyCarbonFootprintMitigationPlanStarted);
        $this->assertCount(1, $started);
        $this->assertTrue($footprint->isMitigationPlanStarted());
    }

    public function testExcludeStageRemovesItsActivitiesAndSourceExclusions(): void
    {
        $footprint = PropertyCarbonFootprintMother::withScope2Answered('property-1', 'footprint-1');
        $purchases = new ActivityPlacement(Scope::Scope3, Source::PurchasedGoodsAndServices);
        $electricity = new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity);
        $footprint->addActivity(ActivityId::generate(), $purchases);
        $footprint->addActivity(ActivityId::generate(), $electricity);
        $footprint->excludeSource(Scope::Scope3, Source::Investments, ExclusionReason::NotApplicable);
        $footprint->clearEvents();

        $footprint->excludeStage(Scope::Scope3, ExclusionReason::NoDataAvailable, 'no data');

        $excluded = array_values(array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof PropertyCarbonFootprintScopeExcluded));
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
        $footprint = PropertyCarbonFootprintMother::withScope2Answered('property-1', 'footprint-1');
        $footprint->excludeStage(Scope::Scope3, ExclusionReason::NoDataAvailable, 'no data');
        $footprint->clearEvents();

        $footprint->excludeStage(Scope::Scope3, ExclusionReason::NoDataAvailable, 'no data');

        $this->assertSame([], $footprint->recordedEvents());
    }

    public function testIncludeStageClearsTheStageExclusion(): void
    {
        $footprint = PropertyCarbonFootprintMother::withScope2Answered('property-1', 'footprint-1');
        $footprint->excludeStage(Scope::Scope3, ExclusionReason::NoDataAvailable, 'no data');
        $footprint->clearEvents();

        $footprint->includeStage(Scope::Scope3);
        $footprint->includeStage(Scope::Scope3);

        $included = array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof PropertyCarbonFootprintScopeIncluded);
        $this->assertCount(1, $included);
        $this->assertFalse($footprint->isStageExcluded(Scope::Scope3));
        $this->assertNull($footprint->stageExclusionReason(Scope::Scope3));
        $this->assertNull($footprint->stageExclusionComment(Scope::Scope3));
    }

    public function testScope1CannotBeExcluded(): void
    {
        $footprint = PropertyCarbonFootprintMother::registered();

        $this->expectException(StageNotExcludableException::class);

        $footprint->excludeStage(Scope::Scope1, ExclusionReason::NoDataAvailable);
    }

    public function testAddActivityAndExcludeSourceAreIgnoredInAnExcludedStage(): void
    {
        $footprint = PropertyCarbonFootprintMother::withScope2Answered('property-1', 'footprint-1');
        $footprint->excludeStage(Scope::Scope3, ExclusionReason::NoDataAvailable);
        $placement = new ActivityPlacement(Scope::Scope3, Source::PurchasedGoodsAndServices);

        $footprint->addActivity(ActivityId::generate(), $placement);
        $footprint->excludeSource(Scope::Scope3, Source::Investments, ExclusionReason::NotApplicable);

        $this->assertCount(0, $footprint->activityIds($placement));
        $this->assertSame([], $footprint->exclusionsFor(Scope::Scope3));
    }

    public function testAddActivityWithProviderIsOnlyRecoveredWithTheSamePlacement(): void
    {
        $footprint = PropertyCarbonFootprintMother::registered();
        $providerId = ProviderId::generate();
        $ownerPlacement = new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport);
        $providerPlacement = new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport, providerId: $providerId);
        $activityId = ActivityId::generate();

        $footprint->addActivity($activityId, $providerPlacement);

        $this->assertCount(0, $footprint->activityIds($ownerPlacement));
        $this->assertCount(1, $footprint->activityIds($providerPlacement));
        $this->assertCount(0, $footprint->activityIds(new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport, providerId: ProviderId::generate())));
        $this->assertTrue($activityId->equals($footprint->activityIds($providerPlacement)[0]));
    }

    public function testExcludeSourceAlsoRemovesProviderActivities(): void
    {
        $footprint = PropertyCarbonFootprintMother::withScope2Answered('property-1', 'footprint-1');
        $ownerPlacement = new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport);
        $providerPlacement = new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport, providerId: ProviderId::generate());
        $footprint->addActivity(ActivityId::generate(), $ownerPlacement);
        $footprint->addActivity(ActivityId::generate(), $providerPlacement);

        $footprint->excludeSource(Scope::Scope3, Source::UpstreamTransport, ExclusionReason::NoDataAvailable);

        $this->assertCount(0, $footprint->activityIds($ownerPlacement));
        $this->assertCount(0, $footprint->activityIds($providerPlacement));
    }

    public function testExcludeStageAlsoRemovesProviderActivities(): void
    {
        $footprint = PropertyCarbonFootprintMother::withScope2Answered('property-1', 'footprint-1');
        $providerPlacement = new ActivityPlacement(Scope::Scope3, Source::BusinessTravel, providerId: ProviderId::generate());
        $footprint->addActivity(ActivityId::generate(), $providerPlacement);

        $footprint->excludeStage(Scope::Scope3, ExclusionReason::NoDataAvailable);

        $this->assertCount(0, $footprint->activityIds($providerPlacement));
    }

    public function testRemoveActivitiesOfProviderKeepsOwnerAndOtherProviderActivities(): void
    {
        $footprint = PropertyCarbonFootprintMother::withScope2Answered('property-1', 'footprint-1');
        $providerId = ProviderId::generate();
        $ownerPlacement = new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport);
        $providerPlacement = new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport, providerId: $providerId);
        $otherProviderPlacement = new ActivityPlacement(Scope::Scope3, Source::OperationsWaste, providerId: ProviderId::generate());
        $footprint->addActivity(ActivityId::generate(), $ownerPlacement);
        $footprint->addActivity(ActivityId::generate(), $providerPlacement);
        $footprint->addActivity(ActivityId::generate(), new ActivityPlacement(Scope::Scope3, Source::OperationsWaste, providerId: $providerId));
        $footprint->addActivity(ActivityId::generate(), $otherProviderPlacement);

        $footprint->removeActivitiesOfProvider($providerId);

        $this->assertCount(1, $footprint->activityIds($ownerPlacement));
        $this->assertCount(0, $footprint->activityIds($providerPlacement));
        $this->assertCount(1, $footprint->activityIds($otherProviderPlacement));
    }

    public function testCompleteFromProvidersAnsweredThrows(): void
    {
        $footprint = PropertyCarbonFootprintMother::withProvidersAnswered('property-1', 'footprint-1');

        $this->expectException(OrganizationalCarbonFootprintNotReadyToCompleteException::class);

        $footprint->complete(new Emissions());
    }

    public function testAnswerEmployeesAdvancesTheStatus(): void
    {
        $footprint = PropertyCarbonFootprintMother::withProvidersAnswered('property-1', 'footprint-1');

        $footprint->answerEmployees();

        $answered = array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof PropertyCarbonFootprintEmployeesAnswered);
        $this->assertCount(1, $answered);
        $this->assertSame(OrganizationalCarbonFootprintStatus::EmployeesAnswered, $footprint->status());
    }

    public function testAnswerEmployeesIsIdempotent(): void
    {
        $footprint = PropertyCarbonFootprintMother::withEmployeesAnswered('property-1', 'footprint-1');

        $footprint->answerEmployees();

        $answered = array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof PropertyCarbonFootprintEmployeesAnswered);
        $this->assertCount(1, $answered);
        $this->assertSame(OrganizationalCarbonFootprintStatus::EmployeesAnswered, $footprint->status());
    }

    public function testAnswerEmployeesOnACompletedFootprintThrows(): void
    {
        $footprint = PropertyCarbonFootprintMother::withEmployeesAnswered('property-1', 'footprint-1');
        $footprint->complete(new Emissions());

        $this->expectException(OrganizationalCarbonFootprintAlreadyCompletedException::class);

        $footprint->answerEmployees();
    }

    public function testAddActivityFromEmployeesIsOnlyRecoveredWithTheSamePlacement(): void
    {
        $footprint = PropertyCarbonFootprintMother::registered();
        $ownerPlacement = new ActivityPlacement(Scope::Scope3, Source::EmployeeCommuting);
        $employeePlacement = new ActivityPlacement(Scope::Scope3, Source::EmployeeCommuting, fromEmployees: true);
        $activityId = ActivityId::generate();

        $footprint->addActivity($activityId, $employeePlacement);

        $this->assertCount(0, $footprint->activityIds($ownerPlacement));
        $this->assertCount(1, $footprint->activityIds($employeePlacement));
        $this->assertTrue($activityId->equals($footprint->activityIds($employeePlacement)[0]));
    }

    public function testAnOwnerActivityIsNotRecoveredWithTheEmployeePlacement(): void
    {
        $footprint = PropertyCarbonFootprintMother::registered();
        $ownerPlacement = new ActivityPlacement(Scope::Scope3, Source::EmployeeCommuting);
        $employeePlacement = new ActivityPlacement(Scope::Scope3, Source::EmployeeCommuting, fromEmployees: true);
        $activityId = ActivityId::generate();

        $footprint->addActivity($activityId, $ownerPlacement);

        $this->assertCount(0, $footprint->activityIds($employeePlacement));
        $this->assertCount(1, $footprint->activityIds($ownerPlacement));
        $this->assertTrue($activityId->equals($footprint->activityIds($ownerPlacement)[0]));
    }

    public function testRemoveEmployeeActivitiesKeepsTheOthers(): void
    {
        $footprint = PropertyCarbonFootprintMother::withScope2Answered('property-1', 'footprint-1');
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
        $footprint = PropertyCarbonFootprintMother::withScope2Answered('property-1', 'footprint-1');
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

    public function testMarkAsPrecalculatedPublishesTheEventAndSetsTheFlag(): void
    {
        $footprint = PropertyCarbonFootprintMother::from('property-1', 'footprint-1');

        $this->assertFalse($footprint->isPrecalculated());

        $footprint->markAsPrecalculated();

        $events = array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof PropertyCarbonFootprintMarkedAsPrecalculated);
        $this->assertCount(1, $events);
        $this->assertTrue($footprint->isPrecalculated());
    }

    public function testMarkAsPrecalculatedIsIdempotent(): void
    {
        $footprint = PropertyCarbonFootprintMother::precalculated('property-1', 'footprint-1');
        $footprint->clearEvents();

        $footprint->markAsPrecalculated();

        $this->assertSame([], $footprint->recordedEvents());
        $this->assertTrue($footprint->isPrecalculated());
    }

    public function testMarkAsPrecalculatedRemovesScope1Scope2AndFuelAndEnergyRelatedActivities(): void
    {
        $footprint = PropertyCarbonFootprintMother::withScope2Answered('property-1', 'footprint-1');
        $fixedFacilities = new ActivityPlacement(Scope::Scope1, Source::FixedFacilities);
        $electricity = new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity);
        $fuelAndEnergy = new ActivityPlacement(Scope::Scope3, Source::FuelAndEnergyRelated);
        $purchases = new ActivityPlacement(Scope::Scope3, Source::PurchasedGoodsAndServices);
        $footprint->addActivity(ActivityId::generate(), $fixedFacilities);
        $footprint->addActivity(ActivityId::generate(), $electricity);
        $footprint->addActivity(ActivityId::generate(), $fuelAndEnergy);
        $footprint->addActivity(ActivityId::generate(), $purchases);
        $footprint->excludeSource(Scope::Scope1, Source::RefrigerantLeaks, ExclusionReason::NotApplicable);

        $footprint->markAsPrecalculated();

        $this->assertCount(0, $footprint->activityIds($fixedFacilities));
        $this->assertCount(0, $footprint->activityIds($electricity));
        $this->assertCount(0, $footprint->activityIds($fuelAndEnergy));
        $this->assertCount(1, $footprint->activityIds($purchases));
        $this->assertTrue($footprint->isSourceExcluded(Scope::Scope1, Source::RefrigerantLeaks));
        $this->assertSame(OrganizationalCarbonFootprintStatus::Scope2Answered, $footprint->status());
    }

    public function testUnmarkAsPrecalculatedPublishesTheEventAndClearsTheFlag(): void
    {
        $footprint = PropertyCarbonFootprintMother::precalculated('property-1', 'footprint-1');
        $footprint->clearEvents();

        $footprint->unmarkAsPrecalculated();

        $events = array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof PropertyCarbonFootprintUnmarkedAsPrecalculated);
        $this->assertCount(1, $events);
        $this->assertFalse($footprint->isPrecalculated());
    }

    public function testUnmarkAsPrecalculatedIsIdempotent(): void
    {
        $footprint = PropertyCarbonFootprintMother::from('property-1', 'footprint-1');
        $footprint->clearEvents();

        $footprint->unmarkAsPrecalculated();

        $this->assertSame([], $footprint->recordedEvents());
        $this->assertFalse($footprint->isPrecalculated());
    }

    public function testUnmarkAsPrecalculatedRemovesScope1Scope2AndFuelAndEnergyRelatedActivities(): void
    {
        $footprint = PropertyCarbonFootprintMother::precalculated('property-1', 'footprint-1');
        $fixedFacilities = new ActivityPlacement(Scope::Scope1, Source::FixedFacilities);
        $electricity = new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity);
        $fuelAndEnergy = new ActivityPlacement(Scope::Scope3, Source::FuelAndEnergyRelated);
        $purchases = new ActivityPlacement(Scope::Scope3, Source::PurchasedGoodsAndServices);
        $footprint->addActivity(ActivityId::generate(), $fixedFacilities);
        $footprint->addActivity(ActivityId::generate(), $electricity);
        $footprint->addActivity(ActivityId::generate(), $fuelAndEnergy);
        $footprint->addActivity(ActivityId::generate(), $purchases);
        $footprint->excludeSource(Scope::Scope3, Source::Investments, ExclusionReason::NotApplicable);

        $footprint->unmarkAsPrecalculated();

        $this->assertCount(0, $footprint->activityIds($fixedFacilities));
        $this->assertCount(0, $footprint->activityIds($electricity));
        $this->assertCount(0, $footprint->activityIds($fuelAndEnergy));
        $this->assertCount(1, $footprint->activityIds($purchases));
        $this->assertTrue($footprint->isSourceExcluded(Scope::Scope3, Source::Investments));
    }

    public function testMarkAsPrecalculatedOnACompletedFootprintThrows(): void
    {
        $footprint = new PropertyCarbonFootprint(
            id: PropertyCarbonFootprintId::generate(),
            status: OrganizationalCarbonFootprintStatus::Completed,
        );

        $this->expectException(OrganizationalCarbonFootprintAlreadyCompletedException::class);

        $footprint->markAsPrecalculated();
    }

    public function testUnmarkAsPrecalculatedOnACompletedFootprintThrows(): void
    {
        $footprint = new PropertyCarbonFootprint(
            id: PropertyCarbonFootprintId::generate(),
            status: OrganizationalCarbonFootprintStatus::Completed,
            precalculated: true,
        );

        $this->expectException(OrganizationalCarbonFootprintAlreadyCompletedException::class);

        $footprint->unmarkAsPrecalculated();
    }

    public function testExcludeStageRemovesFuelAndEnergyRelatedActivities(): void
    {
        $footprint = PropertyCarbonFootprintMother::precalculated('property-1', 'footprint-1');
        $fuelAndEnergy = new ActivityPlacement(Scope::Scope3, Source::FuelAndEnergyRelated);
        $footprint->addActivity(ActivityId::generate(), $fuelAndEnergy);

        $footprint->excludeStage(Scope::Scope3, ExclusionReason::NoDataAvailable);

        $this->assertCount(0, $footprint->activityIds($fuelAndEnergy));
    }

    public function testDeclareIntensityDenominatorsPublishesAndAppliesTheEvent(): void
    {
        $footprint = new PropertyCarbonFootprint(id: PropertyCarbonFootprintId::generate());

        $footprint->declareIntensityDenominators(new IntensityDenominators(stays: 41000, builtAreaM2: '2000.00'));

        $events = array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof PropertyCarbonFootprintIntensityDenominatorsDeclared);
        $this->assertCount(1, $events);
        $this->assertSame(41000, $footprint->intensityDenominators()->stays());
        $this->assertSame('2000.00', $footprint->intensityDenominators()->builtAreaM2());
    }

    public function testDeclareIntensityDenominatorsIsAllowedOnACompletedFootprint(): void
    {
        $footprint = new PropertyCarbonFootprint(
            id: PropertyCarbonFootprintId::generate(),
            status: OrganizationalCarbonFootprintStatus::Completed,
        );

        $footprint->declareIntensityDenominators(new IntensityDenominators(averageHeadcount: '25.50'));

        $this->assertSame('25.50', $footprint->intensityDenominators()->averageHeadcount());
    }

    public function testDeclaringTheSameIntensityDenominatorsDoesNotPublish(): void
    {
        $footprint = new PropertyCarbonFootprint(id: PropertyCarbonFootprintId::generate());
        $footprint->declareIntensityDenominators(new IntensityDenominators(stays: 100));

        $footprint->declareIntensityDenominators(new IntensityDenominators(stays: 100));

        $events = array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof PropertyCarbonFootprintIntensityDenominatorsDeclared);
        $this->assertCount(1, $events);
    }

    public function testApplyMaterialityDecisionsPreexcludesTheOmittedSources(): void
    {
        $footprint = PropertyCarbonFootprintMother::withMaterialityDecisions([
            MaterialityDecisionInputMother::omit(5),
            MaterialityDecisionInputMother::calculate(1),
            MaterialityDecisionInputMother::omit(3, 20.0),
            MaterialityDecisionInputMother::omit(null),
            MaterialityDecisionInputMother::omit(14),
        ], 'property-1', 'footprint-1');

        $waste = $footprint->exclusionFor(Scope::Scope3, Source::OperationsWaste);
        $this->assertNotNull($waste);
        $this->assertSame(ExclusionReason::NotSignificant, $waste->reason());
        $this->assertSame('Análisis de materialidad: relevancia 38,00 % por debajo del umbral 50 %', $waste->comment());
        $this->assertTrue($footprint->isSourceExcluded(Scope::Scope3, Source::FuelAndEnergyRelated));
        $this->assertFalse($footprint->isSourceExcluded(Scope::Scope3, Source::PurchasedGoodsAndServices));
        $this->assertFalse($footprint->isSourceExcluded(Scope::Scope3, Source::Franchises));
        $this->assertCount(5, $footprint->materialityDecisions());
        $this->assertNull($footprint->materialityDecisionFor(Source::Materials));
        $this->assertSame(MaterialityVerdict::Calculate, $footprint->materialityDecisionFor(Source::PurchasedGoodsAndServices)?->verdict());
        $this->assertCount(1, array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof PropertyCarbonFootprintMaterialityDecisionsApplied));
    }

    public function testASecondApplicationIncludesTheSourcesThatBecomeMaterial(): void
    {
        $footprint = PropertyCarbonFootprintMother::withMaterialityDecisions([MaterialityDecisionInputMother::omit(5)]);

        $footprint->applyMaterialityDecisions([MaterialityDecisionInputMother::calculate(5)], []);

        $this->assertFalse($footprint->isSourceExcluded(Scope::Scope3, Source::OperationsWaste));
        $this->assertSame(MaterialityVerdict::Calculate, $footprint->materialityDecisionFor(Source::OperationsWaste)?->verdict());
        $this->assertCount(1, $footprint->materialityDecisions());
    }

    public function testAManualExclusionIsNeverWithdrawnByTheDecisions(): void
    {
        $footprint = PropertyCarbonFootprintMother::withMaterialityAnswered('property-1', 'footprint-1');
        $footprint->excludeSource(Scope::Scope3, Source::PurchasedGoodsAndServices, ExclusionReason::NoDataAvailable);

        $footprint->applyMaterialityDecisions([MaterialityDecisionInputMother::calculate(1)], []);

        $this->assertSame(ExclusionReason::NoDataAvailable, $footprint->exclusionFor(Scope::Scope3, Source::PurchasedGoodsAndServices)?->reason());
    }

    public function testWithScope3AnsweredOnlyTheSourcesWithoutDataAreExcluded(): void
    {
        $footprint = PropertyCarbonFootprintMother::withScope3Answered('property-1', 'footprint-1');
        $footprint->addActivity(ActivityId::generate(), new ActivityPlacement(Scope::Scope3, Source::OperationsWaste));
        $decisions = [MaterialityDecisionInputMother::omit(5), MaterialityDecisionInputMother::omit(4)];

        $footprint->applyMaterialityDecisions($decisions, MaterialityDecisionInputMother::commentsFor($decisions));

        $this->assertTrue($footprint->isSourceExcluded(Scope::Scope3, Source::UpstreamTransport));
        $this->assertFalse($footprint->isSourceExcluded(Scope::Scope3, Source::OperationsWaste));
        $this->assertCount(1, $footprint->activityReferences());
        $this->assertTrue($footprint->hasPendingMaterialityReview());
        $this->assertSame([Source::OperationsWaste], $footprint->pendingMaterialityReviewSources());
    }

    public function testWithScope3AnsweredASourceWithProviderActivitiesIsNotExcluded(): void
    {
        $footprint = PropertyCarbonFootprintMother::withScope3Answered('property-1', 'footprint-1');
        $footprint->addActivity(ActivityId::generate(), new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport, ProviderId::generate()));

        $footprint->applyMaterialityDecisions([MaterialityDecisionInputMother::omit(4)], []);

        $this->assertFalse($footprint->isSourceExcluded(Scope::Scope3, Source::UpstreamTransport));
        $this->assertTrue($footprint->hasPendingMaterialityReview());
    }

    public function testWithScope3AnsweredAManualExclusionOfAMaterialSourceIsPendingOfReview(): void
    {
        $footprint = PropertyCarbonFootprintMother::withScope3Answered('property-1', 'footprint-1');
        $footprint->excludeSource(Scope::Scope3, Source::PurchasedGoodsAndServices, ExclusionReason::NoDataAvailable);

        $footprint->applyMaterialityDecisions([MaterialityDecisionInputMother::calculate(1)], []);

        $this->assertTrue($footprint->isSourceExcluded(Scope::Scope3, Source::PurchasedGoodsAndServices));
        $this->assertTrue($footprint->hasPendingMaterialityReview());
        $this->assertSame([Source::PurchasedGoodsAndServices], $footprint->pendingMaterialityReviewSources());
    }

    public function testWithoutScope3AnsweredThereIsNoPendingReview(): void
    {
        $footprint = PropertyCarbonFootprintMother::withMaterialityDecisions([MaterialityDecisionInputMother::omit(5)]);

        $this->assertFalse($footprint->hasPendingMaterialityReview());
    }

    public function testAnOverriddenDecisionIsNeverTouched(): void
    {
        $footprint = PropertyCarbonFootprintMother::withMaterialityDecisions([MaterialityDecisionInputMother::omit(5)]);
        $footprint->includeSource(Scope::Scope3, Source::OperationsWaste);
        $footprint->overrideMaterialityDecision(Source::OperationsWaste, UserId::generate(), 'Tenemos datos');

        $footprint->applyMaterialityDecisions([MaterialityDecisionInputMother::omit(5, 30.0)], []);

        $this->assertFalse($footprint->isSourceExcluded(Scope::Scope3, Source::OperationsWaste));
        $decision = $footprint->materialityDecisionFor(Source::OperationsWaste);
        $this->assertNotNull($decision);
        $this->assertTrue($decision->isOverridden());
        $this->assertSame(30.0, $decision->scorePercent());
    }

    /** @dataProvider blankJustifications */
    public function testAnOverrideRequiresAJustification(string $justification): void
    {
        $footprint = PropertyCarbonFootprintMother::withMaterialityDecisions([MaterialityDecisionInputMother::omit(5)]);
        $footprint->includeSource(Scope::Scope3, Source::OperationsWaste);

        $this->expectException(MaterialityOverrideJustificationRequiredException::class);

        $footprint->overrideMaterialityDecision(Source::OperationsWaste, UserId::generate(), $justification);
    }

    /** @return iterable<string, array{string}> */
    public static function blankJustifications(): iterable
    {
        yield 'empty' => [''];
        yield 'blank' => ['   '];
    }

    public function testAJustifiedOverrideIsPublished(): void
    {
        $footprint = PropertyCarbonFootprintMother::withMaterialityDecisions([MaterialityDecisionInputMother::omit(5)]);
        $footprint->includeSource(Scope::Scope3, Source::OperationsWaste);

        $footprint->overrideMaterialityDecision(Source::OperationsWaste, UserId::generate(), 'Tenemos datos');

        $this->assertCount(1, array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof PropertyCarbonFootprintMaterialityOverridden));
    }

    public function testAnOverrideIsRevertedWhenTheVerdictChanges(): void
    {
        $footprint = PropertyCarbonFootprintMother::withMaterialityDecisions([MaterialityDecisionInputMother::omit(5)]);
        $footprint->includeSource(Scope::Scope3, Source::OperationsWaste);
        $footprint->overrideMaterialityDecision(Source::OperationsWaste, UserId::generate(), 'Tenemos datos');

        $footprint->applyMaterialityDecisions([MaterialityDecisionInputMother::calculate(5)], []);

        $decision = $footprint->materialityDecisionFor(Source::OperationsWaste);
        $this->assertNotNull($decision);
        $this->assertFalse($decision->isOverridden());
        $this->assertNull($decision->overrideJustification());
        $this->assertCount(1, array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof PropertyCarbonFootprintMaterialityOverrideReverted));
    }

    public function testACompletedFootprintIgnoresTheDecisions(): void
    {
        $footprint = PropertyCarbonFootprintMother::withEmployeesAnswered('property-1', 'footprint-1');
        $footprint->complete(new Emissions());

        $footprint->applyMaterialityDecisions([MaterialityDecisionInputMother::omit(5)], []);
        $footprint->clearMaterialityDecisions();

        $this->assertSame([], $footprint->materialityDecisions());
        $this->assertCount(0, array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof PropertyCarbonFootprintMaterialityDecisionsApplied));
    }

    public function testAnExcludedScope3KeepsTheDecisionsWithoutTouchingExclusions(): void
    {
        $footprint = PropertyCarbonFootprintMother::withMaterialityDecisions([MaterialityDecisionInputMother::omit(5)]);

        $footprint->excludeStage(Scope::Scope3, ExclusionReason::NotApplicable);
        $footprint->applyMaterialityDecisions([MaterialityDecisionInputMother::omit(5), MaterialityDecisionInputMother::omit(4)], []);

        $this->assertCount(2, $footprint->materialityDecisions());
        $this->assertSame([], $footprint->exclusionsFor(Scope::Scope3));
        $this->assertFalse($footprint->hasPendingMaterialityReview());
    }

    public function testOverrideAndRevertPublishAndAreIdempotent(): void
    {
        $footprint = PropertyCarbonFootprintMother::withMaterialityDecisions([MaterialityDecisionInputMother::calculate(1)]);
        $userId = UserId::generate();

        $footprint->overrideMaterialityDecision(Source::PurchasedGoodsAndServices, $userId, 'No aplica');
        $footprint->overrideMaterialityDecision(Source::PurchasedGoodsAndServices, $userId, 'No aplica');
        $footprint->overrideMaterialityDecision(Source::Materials, $userId, 'No aplica');

        $decision = $footprint->materialityDecisionFor(Source::PurchasedGoodsAndServices);
        $this->assertNotNull($decision);
        $this->assertTrue($decision->isOverridden());
        $this->assertEquals($userId, $decision->overriddenByUserId());
        $this->assertNotNull($decision->overriddenAt());
        $this->assertSame('No aplica', $decision->overrideJustification());
        $this->assertCount(1, array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof PropertyCarbonFootprintMaterialityOverridden));

        $footprint->revertMaterialityOverride(Source::PurchasedGoodsAndServices);
        $footprint->revertMaterialityOverride(Source::PurchasedGoodsAndServices);

        $this->assertFalse($decision->isOverridden());
        $this->assertCount(1, array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof PropertyCarbonFootprintMaterialityOverrideReverted));
    }

    public function testClearMaterialityDecisionsWithdrawsOnlyTheRecommendedExclusions(): void
    {
        $footprint = PropertyCarbonFootprintMother::withMaterialityDecisions([
            MaterialityDecisionInputMother::omit(5),
            MaterialityDecisionInputMother::omit(4),
            MaterialityDecisionInputMother::calculate(1),
        ]);
        $footprint->excludeSource(Scope::Scope3, Source::PurchasedGoodsAndServices, ExclusionReason::OutOfScope);
        $footprint->overrideMaterialityDecision(Source::PurchasedGoodsAndServices, UserId::generate(), 'Fuera del perímetro');
        $footprint->excludeSource(Scope::Scope3, Source::UpstreamTransport, ExclusionReason::NoDataAvailable);
        $footprint->overrideMaterialityDecision(Source::UpstreamTransport, UserId::generate(), 'Sin datos');

        $footprint->clearMaterialityDecisions();

        $this->assertFalse($footprint->isSourceExcluded(Scope::Scope3, Source::OperationsWaste));
        $this->assertTrue($footprint->isSourceExcluded(Scope::Scope3, Source::UpstreamTransport));
        $this->assertTrue($footprint->isSourceExcluded(Scope::Scope3, Source::PurchasedGoodsAndServices));
        $this->assertSame([], $footprint->materialityDecisions());
        $this->assertCount(1, array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof PropertyCarbonFootprintMaterialityDecisionsCleared));
    }

    public function testClearWithoutDecisionsDoesNothing(): void
    {
        $footprint = PropertyCarbonFootprintMother::withMaterialityAnswered('property-1', 'footprint-1');

        $footprint->clearMaterialityDecisions();

        $this->assertCount(0, array_filter($footprint->recordedEvents(), static fn($e) => $e instanceof PropertyCarbonFootprintMaterialityDecisionsCleared));
    }
}
