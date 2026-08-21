<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprintReport\Generate;

use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificate;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateRepository;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateType;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateVerificationUrlGenerator;
use App\Balance\Domain\Model\Project\Project;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionFilter;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionStatus;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyRepository;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponse;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseRepository;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportCompensation;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportCompensationLine;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportCompensationProjectLine;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportCompensationScope;
use App\EventOperations\Domain\Model\Event\EventId;

final class EventCompensationCollector
{
    public function __construct(
        private ProjectContributionRepository $projectContributionRepository,
        private ContributionCertificateRepository $contributionCertificateRepository,
        private ContributionCertificateVerificationUrlGenerator $verificationUrlGenerator,
        private ProjectRepository $projectRepository,
        private AttendeeSurveyRepository $attendeeSurveyRepository,
        private AttendeeSurveyResponseRepository $attendeeSurveyResponseRepository,
    ) {}

    public function __invoke(EventId $eventId, Emissions $totalEmissions): EventCarbonFootprintReportCompensation
    {
        /** @var array<string, list<ProjectContribution>> $byScope */
        $byScope = [
            EventCarbonFootprintReportCompensationScope::OwnCompany->value => $this->ownContributions($eventId),
            EventCarbonFootprintReportCompensationScope::Attendees->value => $this->attendeesContributions($eventId),
        ];

        $projectLines = [];
        $traceabilityLines = [];
        $tonnesByScope = [];
        $lastCompensatedAt = null;

        foreach (EventCarbonFootprintReportCompensationScope::cases() as $scope) {
            $tonnes = 0.0;

            /** @var array<string, array{projectName: string, projectLocation: string|null, projectRegistryId: string|null, isCompensationProject: bool, contributions: int, tonnes: float}> $projects */
            $projects = [];

            foreach ($byScope[$scope->value] as $contribution) {
                $certificate = $this->contributionCertificateRepository->findByProjectContributionId($contribution->id());
                $project = $this->project($contribution);

                $tonnes += $contribution->tonnes();
                $traceabilityLines[] = $this->traceabilityLine($scope, $contribution, $certificate, $project);

                $key = $this->projectKey($contribution, $certificate, $project);
                $projects[$key] ??= [
                    'projectName' => $certificate?->projectName() ?? $project?->name() ?? $contribution->originName() ?? '-',
                    'projectLocation' => $certificate?->projectLocation(),
                    'projectRegistryId' => $certificate?->projectRegistryId() ?? $project?->mitecoId(),
                    'isCompensationProject' => $this->isCompensationProject($certificate, $project),
                    'contributions' => 0,
                    'tonnes' => 0.0,
                ];
                ++$projects[$key]['contributions'];
                $projects[$key]['tonnes'] += $contribution->tonnes();

                $paidAt = $contribution->paidAt();
                if (null !== $paidAt && (null === $lastCompensatedAt || $paidAt > $lastCompensatedAt)) {
                    $lastCompensatedAt = $paidAt;
                }
            }

            $tonnesByScope[$scope->value] = $tonnes;

            foreach ($projects as $project) {
                $projectLines[] = new EventCarbonFootprintReportCompensationProjectLine(
                    scope: $scope,
                    projectName: $project['projectName'],
                    projectLocation: $project['projectLocation'],
                    projectRegistryId: $project['projectRegistryId'],
                    isCompensationProject: $project['isCompensationProject'],
                    contributions: $project['contributions'],
                    tonnes: $project['tonnes'],
                );
            }
        }

        $ownTonnes = $tonnesByScope[EventCarbonFootprintReportCompensationScope::OwnCompany->value];
        $attendeesTonnes = $tonnesByScope[EventCarbonFootprintReportCompensationScope::Attendees->value];
        $totalTonnes = $ownTonnes + $attendeesTonnes;

        return new EventCarbonFootprintReportCompensation(
            ownTonnes: $ownTonnes,
            attendeesTonnes: $attendeesTonnes,
            totalTonnes: $totalTonnes,
            coveragePercentage: $this->coveragePercentage($totalTonnes, $totalEmissions),
            ownContributions: count($byScope[EventCarbonFootprintReportCompensationScope::OwnCompany->value]),
            attendeesContributions: count($byScope[EventCarbonFootprintReportCompensationScope::Attendees->value]),
            cutOffAt: new \DateTimeImmutable('NOW'),
            lastCompensatedAt: $lastCompensatedAt,
            projectLines: $projectLines,
            traceabilityLines: $traceabilityLines,
        );
    }

    /** @return list<ProjectContribution> */
    private function ownContributions(EventId $eventId): array
    {
        return array_values($this->projectContributionRepository->findByFilter(new ProjectContributionFilter(
            status: ProjectContributionStatus::Paid,
            origin: ProjectContributionOrigin::EventCarbonFootprint,
            originId: (string) $eventId,
            limit: 0,
        )));
    }

    /** @return list<ProjectContribution> */
    private function attendeesContributions(EventId $eventId): array
    {
        $attendeeSurvey = $this->attendeeSurveyRepository->findByEventId($eventId);

        if (null === $attendeeSurvey) {
            return [];
        }

        $originIds = array_map(
            static fn(AttendeeSurveyResponse $response): string => (string) $response->id(),
            $this->attendeeSurveyResponseRepository->findByAttendeeSurveyId($attendeeSurvey->id()),
        );

        if ([] === $originIds) {
            return [];
        }

        return array_values($this->projectContributionRepository->findByFilter(new ProjectContributionFilter(
            status: ProjectContributionStatus::Paid,
            origin: ProjectContributionOrigin::AttendeeSurveyResponse,
            originIds: $originIds,
            limit: 0,
        )));
    }

    private function traceabilityLine(
        EventCarbonFootprintReportCompensationScope $scope,
        ProjectContribution $contribution,
        ?ContributionCertificate $certificate,
        ?Project $project,
    ): EventCarbonFootprintReportCompensationLine {
        $isOwnCompany = EventCarbonFootprintReportCompensationScope::OwnCompany === $scope;

        return new EventCarbonFootprintReportCompensationLine(
            scope: $scope,
            beneficiaryName: $isOwnCompany ? ($certificate?->beneficiaryName() ?? $contribution->contributorName()) : null,
            certificateCode: $certificate?->code(),
            issuedAt: $certificate?->issuedAt() ?? $contribution->paidAt() ?? $contribution->registeredAt(),
            projectName: $certificate?->projectName() ?? $project?->name() ?? $contribution->originName() ?? '-',
            projectRegistryId: $certificate?->projectRegistryId() ?? $project?->mitecoId(),
            tonnes: $contribution->tonnes(),
            masterSerials: $certificate?->masterSerials() ?? [],
            creditLots: $certificate?->creditLots() ?? [],
            verificationUrl: null !== $certificate
                ? $this->verificationUrlGenerator->generate($certificate->verificationToken(), $certificate->locale())
                : null,
        );
    }

    private function project(ProjectContribution $contribution): ?Project
    {
        $projectId = $contribution->projectId();

        return $projectId instanceof ProjectId ? $this->projectRepository->find($projectId) : null;
    }

    private function projectKey(ProjectContribution $contribution, ?ContributionCertificate $certificate, ?Project $project): string
    {
        $projectId = $contribution->projectId();

        if ($projectId instanceof ProjectId) {
            return (string) $projectId;
        }

        return $certificate?->projectName() ?? $project?->name() ?? $contribution->originName() ?? '-';
    }

    private function isCompensationProject(?ContributionCertificate $certificate, ?Project $project): bool
    {
        if (null !== $certificate) {
            return $certificate->type()->isCompensation();
        }

        return ContributionCertificateType::fromProjectType($project?->projectType())->isCompensation();
    }

    private function coveragePercentage(float $totalTonnes, Emissions $totalEmissions): float
    {
        $emitted = (float) $totalEmissions->co2eq();

        if ($emitted <= 0.0) {
            return 0.0;
        }

        return $totalTonnes * 1000 * 100 / $emitted;
    }
}
