<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\PreventionPlan;

use App\Security\Domain\Model\User\UserId;

final class PreventionPlanVersion
{
    /** @var iterable<ProposedAction>|ProposedActions */
    private iterable $proposedActions = [];

    private PreventionPlanVersionId $id;

    public function __construct(
        private PreventionPlan $preventionPlan,
        private int $versionNumber,
        private UserId $createdBy,
        private \DateTimeImmutable $createdAt,
        ?PreventionPlanVersionId $id = null,
        private PreventionPlanVersionStatus $status = PreventionPlanVersionStatus::InProgress,
    ) {
        $this->id = $id ?? PreventionPlanVersionId::generate();
    }

    public function id(): PreventionPlanVersionId
    {
        return $this->id;
    }

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

    public function status(): PreventionPlanVersionStatus
    {
        return $this->status;
    }

    public function isInProgress(): bool
    {
        return PreventionPlanVersionStatus::InProgress === $this->status;
    }

    public function isClosed(): bool
    {
        return $this->status->isClosed();
    }

    public function markAsLoaded(): void
    {
        $this->status = PreventionPlanVersionStatus::Loaded;
    }

    public function abandon(): void
    {
        $this->status = PreventionPlanVersionStatus::Abandoned;
    }

    public function proposedActions(): ProposedActions
    {
        if ($this->proposedActions instanceof ProposedActions) {
            return $this->proposedActions;
        }

        $this->proposedActions = new ProposedActions(
            array_values(iterator_to_array($this->proposedActions)),
        );

        return $this->proposedActions;
    }
}
