<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\Activity\Activity;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryActivityRepository implements ActivityRepository
{
    /** @param array<string, Activity> $activities */
    public function __construct(private array $activities = []) {}

    public function save(Activity $activity): void
    {
        $this->activities[(string) $activity->id()] = $activity;
    }

    public function findById(ActivityId $id): ?Activity
    {
        return $this->activities[(string) $id] ?? null;
    }

    /**
     * @param ActivityId[] $ids
     *
     * @return Activity[]
     */
    public function findByIds(array $ids): array
    {
        $activities = [];

        foreach ($ids as $id) {
            if (isset($this->activities[(string) $id])) {
                $activities[] = $this->activities[(string) $id];
            }
        }

        return $activities;
    }

    public function remove(Activity $activity): void
    {
        unset($this->activities[(string) $activity->id()]);
    }
}
