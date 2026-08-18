<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Payout\Manual;

use App\Payments\Domain\Model\Payout\PayoutGateway;
use App\Payments\Domain\Model\Payout\PayoutInstruction;
use App\Payments\Domain\Model\Payout\PayoutReference;

class ManualPayoutGateway implements PayoutGateway
{
    public function name(): string
    {
        return 'manual';
    }

    public function send(PayoutInstruction $instruction): PayoutReference
    {
        return new PayoutReference(gateway: $this->name());
    }
}
