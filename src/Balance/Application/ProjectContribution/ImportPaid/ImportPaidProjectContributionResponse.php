<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\ImportPaid;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Shared\Application\CommandBus\CommandResponse;

final class ImportPaidProjectContributionResponse implements CommandResponse
{
    public function __construct(
        public readonly ProjectContributionId $projectContributionId,
        public readonly float $tonnes,
    ) {}
}
