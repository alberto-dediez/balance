<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLanding;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingId;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingOwnerType;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryPublicLandingRepository implements PublicLandingRepository
{
    /** @param array<string, PublicLanding> $publicLandings */
    public function __construct(private array $publicLandings = []) {}

    public function add(PublicLanding $publicLanding): void
    {
        $this->publicLandings[(string) $publicLanding->id()] = $publicLanding;
    }

    public function find(PublicLandingId $publicLandingId): ?PublicLanding
    {
        return $this->publicLandings[(string) $publicLandingId] ?? null;
    }

    public function findByOwner(PublicLandingOwnerType $ownerType, string $ownerId): ?PublicLanding
    {
        foreach ($this->publicLandings as $publicLanding) {
            if ($publicLanding->ownerType() === $ownerType && $publicLanding->ownerId() === $ownerId) {
                return $publicLanding;
            }
        }

        return null;
    }
}
