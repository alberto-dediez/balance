<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\PreventionPlan;

use App\Security\Domain\Model\User\UserId;

final class PreventionPlanVersion
{
    public function __construct(
        private PreventionPlan $preventionPlan,
        private int $versionNumber,
        private UserId $createdBy,
        private \DateTimeImmutable $createdAt,
    ) {}

    public function preventionPlan(): PreventionPlan
    {
        return $this->preventionPlan;
    }

    public function versionNumber(): int
    {
        return $this->versionNumber;
    }

    public function createdBy(): UserId
    {
        return $this->createdBy;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
