<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContribution;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionId;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryProviderContributionRepository implements ProviderContributionRepository
{
    /** @param array<string, ProviderContribution> $providerContributions */
    public function __construct(
        private array $providerContributions = [],
    ) {}

    public function add(ProviderContribution $providerContribution): void
    {
        $this->providerContributions[(string) $providerContribution->id()] = $providerContribution;
    }

    public function find(ProviderContributionId $providerContributionId): ?ProviderContribution
    {
        return $this->providerContributions[(string) $providerContributionId] ?? null;
    }

    public function findByToken(#[\SensitiveParameter] string $token): ?ProviderContribution
    {
        foreach ($this->providerContributions as $providerContribution) {
            if (hash_equals($providerContribution->token(), $token)) {
                return $providerContribution;
            }
        }

        return null;
    }

    /** @return list<ProviderContribution> */
    public function findByEventCarbonFootprintId(EventCarbonFootprintId $eventCarbonFootprintId): array
    {
        $results = [];

        foreach ($this->providerContributions as $providerContribution) {
            $subjectId = $providerContribution->eventCarbonFootprintId();

            if ($subjectId instanceof EventCarbonFootprintId && $subjectId->equals($eventCarbonFootprintId)) {
                $results[] = $providerContribution;
            }
        }

        return $results;
    }
}
