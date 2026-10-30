<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OperatorContribution;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintActivity;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;

class OperatorContributionActivity implements OrganizationalCarbonFootprintActivity
{
    public function __construct(
        private OperatorContributionActivityId $id,
        private OperatorContribution $contribution,
        private ActivityId $activityId,
        private Scope $scope,
        private Source $source,
        private \DateTimeImmutable $addedAt = new \DateTimeImmutable('NOW'),
    ) {}

    public function id(): OperatorContributionActivityId
    {
        return $this->id;
    }

    public function contribution(): OperatorContribution
    {
        return $this->contribution;
    }

    public function activityId(): ActivityId
    {
        return $this->activityId;
    }

    public function scope(): Scope
    {
        return $this->scope;
    }

    public function source(): Source
    {
        return $this->source;
    }

    public function placement(): ActivityPlacement
    {
        return new ActivityPlacement($this->scope, $this->source);
    }

    public function addedAt(): \DateTimeImmutable
    {
        return $this->addedAt;
    }
}
