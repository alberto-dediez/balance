<?php

declare(strict_types=1);

namespace App\Payments\Application\Payout\Statement;

use App\Payments\Domain\Model\Payout\PayoutId;

final class DownloadPayoutStatementRequest
{
    public function __construct(public readonly PayoutId $payoutId) {}
}
