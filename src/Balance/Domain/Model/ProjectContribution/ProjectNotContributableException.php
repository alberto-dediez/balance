<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ProjectContribution;

use App\Balance\Domain\Model\Project\ProjectId;

class ProjectNotContributableException extends \RuntimeException
{
    public function __construct(ProjectId $projectId)
    {
        parent::__construct(
            sprintf('El proyecto %s no admite aportaciones para esta cuenta.', (string) $projectId),
            409,
        );
    }
}
