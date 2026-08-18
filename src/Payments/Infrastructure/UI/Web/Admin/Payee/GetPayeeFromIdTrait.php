<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\UI\Web\Admin\Payee;

use App\Payments\Domain\Model\Payee\Payee;
use App\Payments\Domain\Model\Payee\PayeeId;
use App\Payments\Domain\Model\Payee\PayeeRepository;

trait GetPayeeFromIdTrait
{
    private function getPayeeFromId(string $payeeId, PayeeRepository $repository): Payee
    {
        $payee = $repository->find(new PayeeId($payeeId));

        if (null === $payee) {
            throw $this->createNotFoundException('Beneficiario no encontrado');
        }

        return $payee;
    }
}
