<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Register;

use App\Balance\Domain\Model\ProjectContribution\ContributionAmounts;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Payments\Domain\Model\Payment\PaymentId;

final class RegisterProjectContributionResponse
{
    public function __construct(
        public readonly ProjectContributionId $projectContributionId,
        public readonly PaymentId $paymentId,
        public readonly string $redirectUrl,
        public readonly ContributionAmounts $amounts,
    ) {}
}
