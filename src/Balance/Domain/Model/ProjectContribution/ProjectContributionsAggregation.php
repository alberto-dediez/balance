<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ProjectContribution;

final class ProjectContributionsAggregation
{
    public function __construct(
        public readonly int $contributions,
        public readonly int $paid,
        public readonly int $pending,
        public readonly int $failed,
        public readonly int $expired,
        public readonly float $paidTotalAmount,
        public readonly float $paidContributionAmount,
        public readonly float $paidManagementFeeAmount,
        public readonly float $paidTonnes,
        public readonly float $paidCo2eqKg,
        public readonly float $averagePaidAmount,
    ) {}
}
