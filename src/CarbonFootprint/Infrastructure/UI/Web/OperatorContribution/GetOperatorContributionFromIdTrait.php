<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\OperatorContribution;

use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContribution;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionId;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionRepository;

trait GetOperatorContributionFromIdTrait
{
    private function getOperatorContributionFromId(
        string $contributionId,
        string $companyCarbonFootprintId,
        OperatorContributionRepository $repository,
    ): OperatorContribution {
        $contribution = $repository->find(new OperatorContributionId($contributionId));

        if (null === $contribution
            || $contribution->isRemoved()
            || $companyCarbonFootprintId !== (string) $contribution->companyCarbonFootprintId()
        ) {
            throw $this->createNotFoundException('Aportación del franquiciado o arrendatario no encontrada');
        }

        return $contribution;
    }
}
