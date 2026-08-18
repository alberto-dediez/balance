<?php

declare(strict_types=1);

namespace App\Payments\Application\Payee\Update;

use App\Payments\Domain\Model\Payee\PayeeId;
use App\Payments\Domain\Model\Payee\PayeeStatus;

final class UpdatePayeeResponse
{
    public function __construct(
        public readonly PayeeId $payeeId,
        public readonly PayeeStatus $status,
    ) {}
}
