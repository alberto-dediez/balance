<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\AnswerGeneralInfo;

use App\CarbonFootprint\Application\OrganizationalProviderContribution\AnswerGeneralInfo\AnswerOrganizationalProviderContributionGeneralInfoRequest;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\AnswerGeneralInfo\AnswerOrganizationalProviderContributionGeneralInfoService;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionClosedException;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalProviderContributionPublicServiceTestCase;

class AnswerOrganizationalProviderContributionGeneralInfoServiceTest extends OrganizationalProviderContributionPublicServiceTestCase
{
    private AnswerOrganizationalProviderContributionGeneralInfoService $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = new AnswerOrganizationalProviderContributionGeneralInfoService(
            $this->authenticator,
            $this->contributionRepository,
            '2026-01',
        );
    }

    public function testGrantsConsentAndUpdatesGeneralInfo(): void
    {
        $footprint = $this->propertyFootprint();
        $contribution = $this->requestedContribution((string) $footprint->id(), $this->linkedProvider());

        ($this->sut)(new AnswerOrganizationalProviderContributionGeneralInfoRequest(
            token: $contribution->token(),
            companyName: 'Logística Sur S.L.',
            service: 'Transporte de mercancías',
            consentIp: '127.0.0.1',
            consentUserAgent: 'PHPUnit',
            consentAcceptLanguage: 'es',
        ));

        $this->assertTrue($contribution->hasConsent());
        $this->assertSame('2026-01', $contribution->consentProof()?->policyVersion());
        $this->assertSame('Logística Sur S.L.', $contribution->companyName());
        $this->assertSame('Transporte de mercancías', $contribution->service());
    }

    public function testCompletedFootprintThrows(): void
    {
        $footprint = $this->propertyFootprint();
        $footprint->answerProviders();
        $footprint->answerEmployees();
        $footprint->complete($this->completedEmissions());
        $contribution = $this->requestedContribution((string) $footprint->id(), $this->linkedProvider());

        $this->expectException(OrganizationalProviderContributionClosedException::class);

        ($this->sut)(new AnswerOrganizationalProviderContributionGeneralInfoRequest($contribution->token(), 'Logística Sur', 'Transporte'));
    }
}
