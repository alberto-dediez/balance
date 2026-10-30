<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OperatorContribution\AnswerGeneralInfo;

use App\CarbonFootprint\Application\OperatorContribution\AnswerGeneralInfo\AnswerOperatorContributionGeneralInfoRequest;
use App\CarbonFootprint\Application\OperatorContribution\AnswerGeneralInfo\AnswerOperatorContributionGeneralInfoService;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionClosedException;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionTokenInvalidException;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionStatus;
use App\Tests\Unit\CarbonFootprint\Application\OperatorContribution\OperatorContributionPublicServiceTestCase;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;

class AnswerOperatorContributionGeneralInfoServiceTest extends OperatorContributionPublicServiceTestCase
{
    public function testGrantsTheConsentAndSavesTheContactName(): void
    {
        $contribution = $this->linkedContribution($this->companyFootprint(), consented: false);

        $this->createSUT()(new AnswerOperatorContributionGeneralInfoRequest($contribution->token(), 'Ana', false, '127.0.0.1'));

        $this->assertTrue($contribution->hasConsent());
        $this->assertSame('v1-test', $contribution->consentProof()?->policyVersion());
        $this->assertSame('Ana', $contribution->contactName());
        $this->assertFalse($contribution->isPrecalculated());
    }

    public function testMarkingAsPrecalculatedRemovesThePreviousActivities(): void
    {
        $contribution = $this->linkedContribution($this->companyFootprint());
        $activityId = $this->addActivity($contribution, Scope::Scope1, Source::FixedFacilities, 1000.0);

        $this->createSUT()(new AnswerOperatorContributionGeneralInfoRequest($contribution->token(), null, true));

        $this->assertTrue($contribution->isPrecalculated());
        $this->assertSame([], $contribution->activityReferences());
        $this->assertNull($this->activityRepository->findById($activityId));
    }

    public function testChangingTheModeAfterAnsweringReturnsTheContributionToPending(): void
    {
        $contribution = $this->linkedContribution($this->companyFootprint());
        $this->addActivity($contribution, Scope::Scope1, Source::FixedFacilities, 1000.0);
        $contribution->answer();

        $this->createSUT()(new AnswerOperatorContributionGeneralInfoRequest($contribution->token(), null, true));

        $this->assertSame(ProviderContributionStatus::Pending, $contribution->status());
        $this->assertNull($contribution->answeredAt());
    }

    public function testWithoutChangingTheMarkTheActivitiesAreKept(): void
    {
        $contribution = $this->linkedContribution($this->companyFootprint());
        $activityId = $this->addActivity($contribution, Scope::Scope1, Source::FixedFacilities, 1000.0);
        $spySubscriber = new SpyDomainEventSubscriber();

        $this->createSUT()(new AnswerOperatorContributionGeneralInfoRequest($contribution->token(), null, false));

        $this->assertCount(1, $contribution->activityReferences());
        $this->assertNotNull($this->activityRepository->findById($activityId));
        $this->assertCount(1, $spySubscriber->events());
    }

    public function testACompletedFootprintIsClosed(): void
    {
        $contribution = $this->linkedContribution($this->completedCompanyFootprint(), consented: false);

        $this->expectException(OperatorContributionClosedException::class);

        $this->createSUT()(new AnswerOperatorContributionGeneralInfoRequest($contribution->token(), null, false));
    }

    public function testAnUnknownTokenIsInvalid(): void
    {
        $this->expectException(OperatorContributionTokenInvalidException::class);

        $this->createSUT()(new AnswerOperatorContributionGeneralInfoRequest('unknown', null, false));
    }

    private function createSUT(): AnswerOperatorContributionGeneralInfoService
    {
        return new AnswerOperatorContributionGeneralInfoService($this->authenticator, $this->contributionRepository, $this->activitiesRemover, 'v1-test');
    }
}
