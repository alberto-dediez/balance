<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\CreditPurchase;

use App\Balance\Domain\Model\Project\ProjectId;

class NegativeCreditBalanceException extends \RuntimeException
{
    public function __construct(ProjectId $projectId, float $purchasedTonnes, float $consumedTonnes)
    {
        parent::__construct(
            sprintf(
                'El proyecto %s ha consumido %.4f t sobre las %.4f t compradas: el crédito disponible es negativo.',
                (string) $projectId,
                $consumedTonnes,
                $purchasedTonnes,
            ),
            409,
        );
    }
}
