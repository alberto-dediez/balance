<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\AccountProject;

use App\Balance\Domain\Model\Project\ProjectId;

class ProjectHasAvailableCreditsException extends \RuntimeException
{
    public function __construct(ProjectId $id)
    {
        parent::__construct(
            sprintf('No se puede desadherir el proyecto %s porque la cuenta tiene toneladas disponibles.', (string) $id),
            409,
        );
    }
}
