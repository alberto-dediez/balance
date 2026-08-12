<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PublicLanding;

interface PublicLandingRepository
{
    public function add(PublicLanding $publicLanding): void;

    public function find(PublicLandingId $publicLandingId): ?PublicLanding;

    public function findByOwner(PublicLandingOwnerType $ownerType, string $ownerId): ?PublicLanding;
}
