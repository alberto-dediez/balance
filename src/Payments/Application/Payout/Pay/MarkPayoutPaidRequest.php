<?php

declare(strict_types=1);

namespace App\Payments\Application\Payout\Pay;

use App\Library\Domain\Model\Media\MediaId;
use App\Payments\Domain\Model\Payout\PayoutId;

class MarkPayoutPaidRequest
{
    public function __construct(
        public PayoutId $payoutId,
        public string $reference = '',
        public ?MediaId $evidence = null,
    ) {}
}
