<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\CreditPurchase;

use App\Balance\Domain\Model\Project\ProjectId;

final class CreditPurchaseQuoteChangedException extends \DomainException
{
    public function __construct(ProjectId $projectId)
    {
        parent::__construct(sprintf('El importe de la solicitud de créditos del proyecto %s ha cambiado.', (string) $projectId), 409);
    }
}
