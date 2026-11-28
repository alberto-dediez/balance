<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Contest;

interface ContestLock
{
    public function acquire(ContestId $contestId): void;
}
