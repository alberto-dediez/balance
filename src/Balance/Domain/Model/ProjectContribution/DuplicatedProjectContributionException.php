<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ProjectContribution;

class DuplicatedProjectContributionException extends \RuntimeException
{
    public function __construct(ProjectContributionId $projectContributionId)
    {
        parent::__construct(
            sprintf('La aportación %s ya está registrada', $projectContributionId),
            500,
        );
    }
}
