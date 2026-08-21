<?php

declare(strict_types=1);

namespace App\Balance\Application\Project\Credits;

use App\Balance\Domain\Model\CreditPurchase\CreditBalance;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;

final class ProjectCreditAvailability
{
    public function __construct(
        private CreditPurchaseRepository $creditPurchaseRepository,
        private ProjectContributionRepository $projectContributionRepository,
    ) {}

    public function unconsumedTonnes(ProjectId $projectId, ?\DateTimeImmutable $now = null): float
    {
        $now ??= new \DateTimeImmutable('NOW');

        return $this->creditPurchaseRepository->purchasedTonnesForProject($projectId)
            - $this->projectContributionRepository->committedTonnesForProject($projectId, $now);
    }

    public function hasUnconsumedCredits(ProjectId $projectId, ?\DateTimeImmutable $now = null): bool
    {
        return $this->unconsumedTonnes($projectId, $now) > CreditBalance::TONNE_TOLERANCE;
    }
}
