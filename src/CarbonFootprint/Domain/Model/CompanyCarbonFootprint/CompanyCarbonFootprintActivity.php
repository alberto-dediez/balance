<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintActivity;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Property\Domain\Model\Property\PropertyId;

class CompanyCarbonFootprintActivity implements OrganizationalCarbonFootprintActivity
{
    public function __construct(
        private CompanyCarbonFootprintActivityId $id,
        private CompanyCarbonFootprint $companyCarbonFootprint,
        private ActivityId $activityId,
        private Scope $scope,
        private Source $source,
        private ?PropertyId $propertyId = null,
        private \DateTimeImmutable $addedAt = new \DateTimeImmutable('NOW'),
    ) {}

    public function id(): CompanyCarbonFootprintActivityId
    {
        return $this->id;
    }

    public function companyCarbonFootprint(): CompanyCarbonFootprint
    {
        return $this->companyCarbonFootprint;
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

    public function propertyId(): ?PropertyId
    {
        return $this->propertyId;
    }

    public function placement(): ActivityPlacement
    {
        return new ActivityPlacement($this->scope, $this->source, propertyId: $this->propertyId);
    }

    public function addedAt(): \DateTimeImmutable
    {
        return $this->addedAt;
    }
}
