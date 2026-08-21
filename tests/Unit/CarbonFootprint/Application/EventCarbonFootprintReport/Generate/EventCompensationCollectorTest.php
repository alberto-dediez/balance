<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EventCarbonFootprintReport\Generate;

use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\CarbonFootprint\Application\EventCarbonFootprintReport\Generate\EventCompensationCollector;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportCompensationScope;
use App\EventOperations\Domain\Model\Event\EventId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Application\ContributionCertificate\Generate\FakeContributionCertificateVerificationUrlGenerator;
use App\Tests\Unit\Balance\Domain\Model\ContributionCertificate\ContributionCertificateMother;
use App\Tests\Unit\Balance\Domain\Model\ContributionCertificate\ContributionCertificateRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseRepositoryMother;
use PHPUnit\Framework\TestCase;

class EventCompensationCollectorTest extends TestCase
{
    private const string EVENT_ID = '11111111-1111-4111-8111-111111111111';

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testReturnsNoCompensationsWhenThereAreNone(): void
    {
        $context = $this->context();

        $compensation = ($context['sut'])(new EventId(self::EVENT_ID), new Emissions('1000', '1000', '0', '0'));

        $this->assertFalse($compensation->hasCompensations());
        $this->assertSame(0.0, $compensation->totalTonnes);
        $this->assertSame(0.0, $compensation->coveragePercentage);
        $this->assertNull($compensation->lastCompensatedAt);
        $this->assertSame([], $compensation->projectLines);
        $this->assertSame([], $compensation->traceabilityLines);
    }

    public function testSeparatesOwnCompanyAndAttendeesCompensations(): void
    {
        $context = $this->context();
        $accountId = new AccountId('account-id');

        $context['projectContributions']->add(ProjectContributionMother::paid(
            accountId: $accountId,
            projectId: $context['projectId'],
            tonnes: 2.0,
            originId: self::EVENT_ID,
            origin: ProjectContributionOrigin::EventCarbonFootprint,
        ));

        $context['projectContributions']->add(ProjectContributionMother::paidFromAttendeeSurveyResponse(
            $accountId,
            $context['projectId'],
            $context['responseId'],
            0.5,
        ));

        $compensation = ($context['sut'])(new EventId(self::EVENT_ID), new Emissions('5000', '5000', '0', '0'));

        $this->assertTrue($compensation->hasCompensations());
        $this->assertSame(2.0, $compensation->ownTonnes);
        $this->assertSame(0.5, $compensation->attendeesTonnes);
        $this->assertSame(2.5, $compensation->totalTonnes);
        $this->assertSame(1, $compensation->ownContributions);
        $this->assertSame(1, $compensation->attendeesContributions);
        $this->assertSame(50.0, $compensation->coveragePercentage);
        $this->assertNotNull($compensation->lastCompensatedAt);
    }

    public function testIgnoresContributionsThatAreNotPaid(): void
    {
        $context = $this->context();
        $accountId = new AccountId('account-id');

        $context['projectContributions']->add(ProjectContributionMother::pending(
            accountId: $accountId,
            projectId: $context['projectId'],
            tonnes: 3.0,
            originId: self::EVENT_ID,
            origin: ProjectContributionOrigin::EventCarbonFootprint,
        ));

        $context['projectContributions']->add(ProjectContributionMother::pendingFromAttendeeSurveyResponse(
            $accountId,
            $context['projectId'],
            $context['responseId'],
            1.0,
        ));

        $compensation = ($context['sut'])(new EventId(self::EVENT_ID), new Emissions('1000', '1000', '0', '0'));

        $this->assertFalse($compensation->hasCompensations());
        $this->assertSame(0.0, $compensation->totalTonnes);
    }

    public function testGroupsContributionsOfTheSameScopeAndProject(): void
    {
        $context = $this->context();
        $accountId = new AccountId('account-id');

        foreach ([1.0, 2.0] as $tonnes) {
            $context['projectContributions']->add(ProjectContributionMother::paid(
                accountId: $accountId,
                projectId: $context['projectId'],
                tonnes: $tonnes,
                originId: self::EVENT_ID,
                origin: ProjectContributionOrigin::EventCarbonFootprint,
            ));
        }

        $compensation = ($context['sut'])(new EventId(self::EVENT_ID), new Emissions('10000', '10000', '0', '0'));

        $this->assertCount(1, $compensation->projectLines);
        $this->assertSame(EventCarbonFootprintReportCompensationScope::OwnCompany, $compensation->projectLines[0]->scope);
        $this->assertSame('Reforestación', $compensation->projectLines[0]->projectName);
        $this->assertSame(2, $compensation->projectLines[0]->contributions);
        $this->assertSame(3.0, $compensation->projectLines[0]->tonnes);
        $this->assertTrue($compensation->projectLines[0]->isCompensationProject);
    }

    public function testAnonymizesTheTraceabilityOfTheAttendees(): void
    {
        $context = $this->context();
        $accountId = new AccountId('account-id');

        $attendeeContribution = ProjectContributionMother::paidFromAttendeeSurveyResponse(
            $accountId,
            $context['projectId'],
            $context['responseId'],
            0.5,
        );
        $context['projectContributions']->add($attendeeContribution);
        $context['certificates']->add(ContributionCertificateMother::issued(
            accountId: $accountId,
            projectContributionId: $attendeeContribution->id(),
            code: 'ATT-0001-0001',
        ));

        $ownContribution = ProjectContributionMother::paid(
            accountId: $accountId,
            projectId: $context['projectId'],
            tonnes: 1.0,
            originId: self::EVENT_ID,
            origin: ProjectContributionOrigin::EventCarbonFootprint,
        );
        $context['projectContributions']->add($ownContribution);
        $context['certificates']->add(ContributionCertificateMother::issued(
            accountId: $accountId,
            projectContributionId: $ownContribution->id(),
            code: 'OWN-0001-0001',
            beneficiaryIsCompany: true,
        ));

        $compensation = ($context['sut'])(new EventId(self::EVENT_ID), new Emissions('5000', '5000', '0', '0'));

        $ownLine = $this->lineOf($compensation->traceabilityLines, EventCarbonFootprintReportCompensationScope::OwnCompany);
        $attendeeLine = $this->lineOf($compensation->traceabilityLines, EventCarbonFootprintReportCompensationScope::Attendees);

        $this->assertSame('Acme SL', $ownLine->beneficiaryName);
        $this->assertSame('OWN-0001-0001', $ownLine->certificateCode);
        $this->assertSame(['2024-b012'], $ownLine->masterSerials);
        $this->assertStringContainsString('https://example.com/', (string) $ownLine->verificationUrl);

        $this->assertNull($attendeeLine->beneficiaryName);
        $this->assertSame('ATT-0001-0001', $attendeeLine->certificateCode);
    }

    public function testPaidContributionWithoutCertificateCountsWithoutSerials(): void
    {
        $context = $this->context();

        $context['projectContributions']->add(ProjectContributionMother::paid(
            accountId: new AccountId('account-id'),
            projectId: $context['projectId'],
            tonnes: 4.0,
            originId: self::EVENT_ID,
            origin: ProjectContributionOrigin::EventCarbonFootprint,
        ));

        $compensation = ($context['sut'])(new EventId(self::EVENT_ID), new Emissions('8000', '8000', '0', '0'));

        $this->assertSame(4.0, $compensation->totalTonnes);
        $this->assertCount(1, $compensation->traceabilityLines);
        $this->assertNull($compensation->traceabilityLines[0]->certificateCode);
        $this->assertNull($compensation->traceabilityLines[0]->verificationUrl);
        $this->assertSame([], $compensation->traceabilityLines[0]->masterSerials);
        $this->assertSame([], $compensation->traceabilityLines[0]->creditLots);
        $this->assertSame('Reforestación', $compensation->traceabilityLines[0]->projectName);
    }

    public function testCoverageIsZeroWhenThereAreNoEmissions(): void
    {
        $context = $this->context();

        $context['projectContributions']->add(ProjectContributionMother::paid(
            accountId: new AccountId('account-id'),
            projectId: $context['projectId'],
            tonnes: 1.0,
            originId: self::EVENT_ID,
            origin: ProjectContributionOrigin::EventCarbonFootprint,
        ));

        $compensation = ($context['sut'])(new EventId(self::EVENT_ID), new Emissions());

        $this->assertSame(0.0, $compensation->coveragePercentage);
        $this->assertSame(1.0, $compensation->totalTonnes);
    }

    /**
     * @param list<\App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportCompensationLine> $lines
     */
    private function lineOf(array $lines, EventCarbonFootprintReportCompensationScope $scope): \App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportCompensationLine
    {
        foreach ($lines as $line) {
            if ($scope === $line->scope) {
                return $line;
            }
        }

        $this->fail(sprintf('No traceability line for scope %s', $scope->value));
    }

    /**
     * @return array{sut: EventCompensationCollector, projectContributions: \App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectContributionRepository, certificates: \App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryContributionCertificateRepository, projectId: ProjectId, responseId: string}
     */
    private function context(): array
    {
        $projectContributions = ProjectContributionRepositoryMother::inMemory();
        $certificates = ContributionCertificateRepositoryMother::inMemory();
        $projects = ProjectRepositoryMother::inMemory();
        $attendeeSurveys = AttendeeSurveyRepositoryMother::inMemory();
        $attendeeSurveyResponses = AttendeeSurveyResponseRepositoryMother::inMemory();

        $project = ProjectMother::fromNameAndId('Reforestación', (string) ProjectId::generate());
        $projects->add($project);

        $attendeeSurvey = AttendeeSurveyMother::fromEventId(self::EVENT_ID);
        $attendeeSurveys->add($attendeeSurvey);

        $response = AttendeeSurveyResponseMother::fromAttendeeSurveyId((string) $attendeeSurvey->id());
        $attendeeSurveyResponses->add($response);

        return [
            'sut' => new EventCompensationCollector(
                $projectContributions,
                $certificates,
                new FakeContributionCertificateVerificationUrlGenerator(),
                $projects,
                $attendeeSurveys,
                $attendeeSurveyResponses,
            ),
            'projectContributions' => $projectContributions,
            'certificates' => $certificates,
            'projectId' => $project->id(),
            'responseId' => (string) $response->id(),
        ];
    }
}
