<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Expire;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionStatus;
use App\Shared\Application\CommandBus\CommandResponse;

final class ExpireProjectContributionResponse implements CommandResponse
{
    public function __construct(
        public readonly ProjectContributionStatus $status,
        public readonly bool $changed,
    ) {}
}
