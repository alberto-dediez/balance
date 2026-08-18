<?php

declare(strict_types=1);

namespace App\Payments\Application\Payee\Block;

use App\Payments\Domain\Model\Payee\PayeeId;

final class BlockPayeeRequest
{
    public function __construct(public readonly PayeeId $payeeId) {}
}
