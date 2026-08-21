<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Contribution;

use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionFilter;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;

final class OriginCompensationsCalculator
{
    /** @var array<string, string> */
    private array $projectNames = [];

    public function __construct(
        private ProjectContributionRepository $projectContributionRepository,
        private ProjectRepository $projectRepository,
    ) {}

    /**
     * @param list<string> $originIds
     *
     * @return array<string, OriginCompensation>
     */
    public function calculate(ProjectContributionOrigin $origin, array $originIds): array
    {
        if ([] === $originIds) {
            return [];
        }

        $projects = [];
        $amounts = [];
        $co2eqKgs = [];

        $projectContributions = $this->projectContributionRepository->findByFilter(
            new ProjectContributionFilter(origin: $origin, originIds: $originIds, limit: 0),
        );

        foreach ($projectContributions as $projectContribution) {
            $originId = $projectContribution->originId();

            if (null === $originId || !$projectContribution->isPaid()) {
                continue;
            }

            $co2eqKgs[$originId] = ($co2eqKgs[$originId] ?? 0.0)
                + ($projectContribution->co2eqKg() ?? $projectContribution->tonnes() * 1000);

            if (!$projectContribution->isOwnCreditFunded()) {
                $amounts[$originId] = ($amounts[$originId] ?? 0.0) + $projectContribution->totalAmount();
            }

            $name = $this->projectName($projectContribution->projectId());

            if ('' !== $name && !in_array($name, $projects[$originId] ?? [], true)) {
                $projects[$originId][] = $name;
            }
        }

        $compensations = [];

        foreach ($co2eqKgs as $originId => $co2eqKg) {
            $compensations[$originId] = new OriginCompensation(
                projects: implode(', ', $projects[$originId] ?? []),
                amount: $amounts[$originId] ?? 0.0,
                co2eqKg: $co2eqKg,
            );
        }

        return $compensations;
    }

    private function projectName(?ProjectId $projectId): string
    {
        if (!$projectId instanceof ProjectId) {
            return '';
        }

        $key = (string) $projectId;

        if (!isset($this->projectNames[$key])) {
            $project = $this->projectRepository->find($projectId);
            $this->projectNames[$key] = null !== $project ? $project->name() : '';
        }

        return $this->projectNames[$key];
    }
}
