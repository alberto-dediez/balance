<?php

declare(strict_types=1);

namespace App\Payments\Application\Payee\Block;

use App\Payments\Domain\Model\Payee\PayeeStatus;

final class BlockPayeeResponse
{
    public function __construct(public readonly PayeeStatus $status) {}
}
