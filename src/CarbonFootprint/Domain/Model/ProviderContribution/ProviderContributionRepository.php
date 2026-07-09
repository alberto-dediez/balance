<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ProviderContribution;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;

interface ProviderContributionRepository
{
    public function add(ProviderContribution $providerContribution): void;

    public function find(ProviderContributionId $providerContributionId): ?ProviderContribution;

    public function findByToken(#[\SensitiveParameter] string $token): ?ProviderContribution;

    /** @return list<ProviderContribution> */
    public function findByEventCarbonFootprintId(EventCarbonFootprintId $eventCarbonFootprintId): array;
}
