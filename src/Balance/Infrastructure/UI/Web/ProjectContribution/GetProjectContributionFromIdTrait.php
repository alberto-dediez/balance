<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\ProjectContribution;

use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;

trait GetProjectContributionFromIdTrait
{
    private function getProjectContributionFromId(
        string $projectContributionId,
        ProjectContributionRepository $repository,
    ): ProjectContribution {
        $projectContribution = $repository->find(new ProjectContributionId($projectContributionId));

        if (null === $projectContribution) {
            throw $this->createNotFoundException('Aportación no encontrada');
        }

        return $projectContribution;
    }
}
