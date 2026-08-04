<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\CreditPurchase;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;

trait GetCreditPurchaseFromIdTrait
{
    private function getCreditPurchaseFromId(string $creditPurchaseId, CreditPurchaseRepository $repository): CreditPurchase
    {
        $creditPurchase = $repository->find(new CreditPurchaseId($creditPurchaseId));

        if (null === $creditPurchase) {
            throw $this->createNotFoundException('Compra de créditos no encontrada');
        }

        return $creditPurchase;
    }
}
