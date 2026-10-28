<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintActivity;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\EventOperations\Domain\Model\Provider\ProviderId;

class PropertyCarbonFootprintActivity implements OrganizationalCarbonFootprintActivity
{
    public function __construct(
        private PropertyCarbonFootprintActivityId $id,
        private PropertyCarbonFootprint $propertyCarbonFootprint,
        private ActivityId $activityId,
        private Scope $scope,
        private Source $source,
        private ?ProviderId $providerId = null,
        private \DateTimeImmutable $addedAt = new \DateTimeImmutable('NOW'),
    ) {}

    public function id(): PropertyCarbonFootprintActivityId
    {
        return $this->id;
    }

    public function propertyCarbonFootprint(): PropertyCarbonFootprint
    {
        return $this->propertyCarbonFootprint;
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

    public function providerId(): ?ProviderId
    {
        return $this->providerId;
    }

    public function placement(): ActivityPlacement
    {
        return new ActivityPlacement($this->scope, $this->source, providerId: $this->providerId);
    }

    public function addedAt(): \DateTimeImmutable
    {
        return $this->addedAt;
    }
}
