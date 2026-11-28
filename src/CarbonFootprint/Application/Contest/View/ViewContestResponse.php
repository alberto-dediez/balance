<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Contest\View;

use App\CarbonFootprint\Domain\Model\Contest\Contest;

class ViewContestResponse
{
    /** @param array<string, string> $hostNames */
    public function __construct(
        public Contest $contest,
        public array $hostNames = [],
    ) {}
}
