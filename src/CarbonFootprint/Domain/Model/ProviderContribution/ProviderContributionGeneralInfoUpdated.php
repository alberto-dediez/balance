<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ProviderContribution;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ProviderContributionGeneralInfoUpdated extends DomainEvent
{
    protected static string $eventName = 'providerContributionGeneralInfoUpdated';

    public function __construct(
        private ProviderContributionId $providerContributionId,
        private ?string $companyName,
        private ?string $companyType,
        private ?string $service,
        private ?int $workersAssembly,
        private ?int $workersCelebration,
        private ?int $workersBreakdown,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function providerContributionId(): ProviderContributionId
    {
        return $this->providerContributionId;
    }

    public function companyName(): ?string
    {
        return $this->companyName;
    }

    public function companyType(): ?string
    {
        return $this->companyType;
    }

    public function service(): ?string
    {
        return $this->service;
    }

    public function workersAssembly(): ?int
    {
        return $this->workersAssembly;
    }

    public function workersCelebration(): ?int
    {
        return $this->workersCelebration;
    }

    public function workersBreakdown(): ?int
    {
        return $this->workersBreakdown;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->providerContributionId();
    }

    public function payload(): array
    {
        return [
            'providerContributionId' => (string) $this->providerContributionId,
            'companyName' => $this->companyName,
            'companyType' => $this->companyType,
            'service' => $this->service,
            'workersAssembly' => $this->workersAssembly,
            'workersCelebration' => $this->workersCelebration,
            'workersBreakdown' => $this->workersBreakdown,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            providerContributionId: new ProviderContributionId($payload['providerContributionId']),
            companyName: $payload['companyName'] ?? null,
            companyType: $payload['companyType'] ?? null,
            service: $payload['service'] ?? null,
            workersAssembly: $payload['workersAssembly'] ?? null,
            workersCelebration: $payload['workersCelebration'] ?? null,
            workersBreakdown: $payload['workersBreakdown'] ?? null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
