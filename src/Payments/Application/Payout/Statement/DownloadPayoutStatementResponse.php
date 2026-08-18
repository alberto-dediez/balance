<?php

declare(strict_types=1);

namespace App\Payments\Application\Payout\Statement;

final class DownloadPayoutStatementResponse
{
    public function __construct(
        public readonly string $path,
        public readonly string $filename,
    ) {}
}
