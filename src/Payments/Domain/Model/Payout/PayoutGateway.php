<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payout;

interface PayoutGateway
{
    public function name(): string;

    public function send(PayoutInstruction $instruction): PayoutReference;
}
