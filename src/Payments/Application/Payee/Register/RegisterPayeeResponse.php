<?php

declare(strict_types=1);

namespace App\Payments\Application\Payee\Register;

use App\Payments\Domain\Model\Payee\PayeeId;
use App\Payments\Domain\Model\Payee\PayeeStatus;

final class RegisterPayeeResponse
{
    public function __construct(
        public readonly PayeeId $payeeId,
        public readonly string $legalName,
        public readonly PayeeStatus $status,
    ) {}
}
