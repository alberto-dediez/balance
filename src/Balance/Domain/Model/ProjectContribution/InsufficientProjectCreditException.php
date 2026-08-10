<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ProjectContribution;

use App\Balance\Domain\Model\Project\ProjectId;

class InsufficientProjectCreditException extends \RuntimeException
{
    public function __construct(ProjectId $projectId, float $requestedTonnes, float $availableTonnes)
    {
        parent::__construct(
            sprintf(
                'El proyecto %s no tiene crédito suficiente: se solicitan %.4f t y hay %.4f t disponibles.',
                (string) $projectId,
                $requestedTonnes,
                $availableTonnes,
            ),
            409,
        );
    }
}
