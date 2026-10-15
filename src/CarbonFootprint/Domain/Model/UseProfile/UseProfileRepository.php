<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\UseProfile;

interface UseProfileRepository
{
    public function add(UseProfile $useProfile): void;

    public function remove(UseProfile $useProfile): void;

    public function find(UseProfileId $useProfileId): ?UseProfile;

    /** @return array<UseProfile> */
    public function findByFilter(UseProfileFilter $filter): array;

    public function totalResultsByFilter(UseProfileFilter $filter): int;
}
