<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Consent;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyId;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseConsentGranted;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionConsentGranted;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionConsentGranted;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationConsentGranted;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Consent\ConsentProof;
use App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Domain\Model\Consent\ConsentProofMother;
use PHPUnit\Framework\TestCase;

class ConsentGrantedTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testTheAttendeeSurveyResponseRecordsTheConsent(): void
    {
        $attendeeSurveyResponse = AttendeeSurveyResponseMother::withConsent((string) AttendeeSurveyId::generate());

        $this->assertTrue($attendeeSurveyResponse->hasConsent());
        $this->assertSame(ConsentProofMother::IP, $attendeeSurveyResponse->consentProof()?->ip());
        $this->assertInstanceOf(AttendeeSurveyResponseConsentGranted::class, $this->spySubscriber->lastEvent());
    }

    public function testTheTripCalculationRecordsTheConsent(): void
    {
        $tripCalculation = TripCalculationMother::withConsent((string) TripCalculationLinkId::generate());

        $this->assertTrue($tripCalculation->hasConsent());
        $this->assertSame(ConsentProofMother::IP, $tripCalculation->consentProof()?->ip());
        $this->assertInstanceOf(TripCalculationConsentGranted::class, $this->spySubscriber->lastEvent());
    }

    public function testTheProviderContributionRecordsTheConsent(): void
    {
        $providerContribution = ProviderContributionMother::withConsent();

        $this->assertTrue($providerContribution->hasConsent());
        $this->assertSame(ConsentProofMother::IP, $providerContribution->consentProof()?->ip());
        $this->assertInstanceOf(ProviderContributionConsentGranted::class, $this->spySubscriber->lastEvent());
    }

    public function testTheEmployeeContributionRecordsTheConsent(): void
    {
        $employeeContribution = EmployeeContributionMother::withConsent();

        $this->assertTrue($employeeContribution->hasConsent());
        $this->assertSame(ConsentProofMother::IP, $employeeContribution->consentProof()?->ip());
        $this->assertInstanceOf(EmployeeContributionConsentGranted::class, $this->spySubscriber->lastEvent());
    }

    public function testANewConsentOverwritesThePreviousProof(): void
    {
        $providerContribution = ProviderContributionMother::withConsent();

        $providerContribution->grantConsent(ConsentProofMother::granted(
            consentedAt: '2026-09-01 08:30:00',
            policyVersion: '2026-09-01',
            ip: '10.0.0.7',
        ));

        $consentProof = $providerContribution->consentProof();

        $this->assertNotNull($consentProof);
        $this->assertSame('2026-09-01 08:30:00', $consentProof->consentedAt()?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-01', $consentProof->policyVersion());
        $this->assertSame('10.0.0.7', $consentProof->ip());
    }

    public function testThePayloadOfTheAttendeeSurveyResponseEventExcludesTheIpAndTheUserAgent(): void
    {
        $event = new AttendeeSurveyResponseConsentGranted(
            AttendeeSurveyResponseMother::random()->id(),
            ConsentProofMother::granted(),
        );

        $this->assertPayloadWithoutIdentifyingData($event->payload());

        $rebuilt = AttendeeSurveyResponseConsentGranted::rebuildFromPayload(DomainEventId::generate(), $event->occurredOn(), $event->payload());

        $this->assertRebuiltWithoutIdentifyingData($rebuilt->consentProof());
    }

    public function testThePayloadOfTheTripCalculationEventExcludesTheIpAndTheUserAgent(): void
    {
        $event = new TripCalculationConsentGranted(
            TripCalculationMother::random()->id(),
            ConsentProofMother::granted(),
        );

        $this->assertPayloadWithoutIdentifyingData($event->payload());

        $rebuilt = TripCalculationConsentGranted::rebuildFromPayload(DomainEventId::generate(), $event->occurredOn(), $event->payload());

        $this->assertRebuiltWithoutIdentifyingData($rebuilt->consentProof());
    }

    public function testThePayloadOfTheProviderContributionEventExcludesTheIpAndTheUserAgent(): void
    {
        $event = new ProviderContributionConsentGranted(
            ProviderContributionMother::random()->id(),
            ConsentProofMother::granted(),
        );

        $this->assertPayloadWithoutIdentifyingData($event->payload());

        $rebuilt = ProviderContributionConsentGranted::rebuildFromPayload(DomainEventId::generate(), $event->occurredOn(), $event->payload());

        $this->assertRebuiltWithoutIdentifyingData($rebuilt->consentProof());
    }

    public function testThePayloadOfTheEmployeeContributionEventExcludesTheIpAndTheUserAgent(): void
    {
        $event = new EmployeeContributionConsentGranted(
            EmployeeContributionMother::random()->id(),
            ConsentProofMother::granted(),
        );

        $this->assertPayloadWithoutIdentifyingData($event->payload());

        $rebuilt = EmployeeContributionConsentGranted::rebuildFromPayload(DomainEventId::generate(), $event->occurredOn(), $event->payload());

        $this->assertRebuiltWithoutIdentifyingData($rebuilt->consentProof());
    }

    /** @param array<string, mixed> $payload */
    private function assertPayloadWithoutIdentifyingData(array $payload): void
    {
        $this->assertSame(ConsentProofMother::POLICY_VERSION, $payload['policyVersion']);
        $this->assertSame(ConsentProofMother::LOCALE, $payload['locale']);
        $this->assertArrayNotHasKey('ip', $payload);
        $this->assertArrayNotHasKey('userAgent', $payload);
        $this->assertArrayNotHasKey('acceptLanguage', $payload);
        $this->assertStringNotContainsString(ConsentProofMother::IP, json_encode($payload, JSON_THROW_ON_ERROR));
    }

    private function assertRebuiltWithoutIdentifyingData(ConsentProof $consentProof): void
    {
        $this->assertSame(ConsentProofMother::CONSENTED_AT, $consentProof->consentedAt()?->format('Y-m-d H:i:s'));
        $this->assertSame(ConsentProofMother::POLICY_VERSION, $consentProof->policyVersion());
        $this->assertNull($consentProof->ip());
        $this->assertNull($consentProof->userAgent());
        $this->assertNull($consentProof->acceptLanguage());
    }
}
