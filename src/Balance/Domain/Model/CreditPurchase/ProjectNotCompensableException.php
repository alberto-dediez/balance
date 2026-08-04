<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\CreditPurchase;

use App\Balance\Domain\Model\Project\ProjectId;

class ProjectNotCompensableException extends \RuntimeException
{
    public function __construct(ProjectId $id)
    {
        parent::__construct(sprintf('El proyecto %s no es de compensación de emisiones.', (string) $id), 409);
    }
}
