<?php

declare(strict_types=1);

namespace App\Balance\Application\Settlement\Unsettle;

final class UnsettleLedgerEntriesResponse
{
    public function __construct(public readonly int $releasedCount = 0) {}
}
