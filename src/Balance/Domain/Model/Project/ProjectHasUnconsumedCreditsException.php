<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\Project;

class ProjectHasUnconsumedCreditsException extends \RuntimeException
{
    public function __construct(ProjectId $id, float $unconsumedTonnes)
    {
        parent::__construct(
            sprintf(
                'No se puede cambiar el coste por tonelada del proyecto %s porque quedan %.6f t de crédito sin consumir.',
                (string) $id,
                $unconsumedTonnes,
            ),
            409,
        );
    }
}
