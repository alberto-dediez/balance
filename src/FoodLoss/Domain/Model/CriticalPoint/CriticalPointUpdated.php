<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\CriticalPoint;

use App\FoodLoss\Domain\DomainEvent;
use App\FoodLoss\Domain\Model\Action\Priority;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CriticalPointUpdated extends DomainEvent
{
    protected static string $eventName = 'criticalPointUpdated';

    public function __construct(
        private readonly CriticalPointId $criticalPointId,
        private readonly string $name,
        private readonly PropertyId $propertyId,
        private readonly SupplyChainStage $supplyChainStage,
        private readonly PriorityAssessment $prevention,
        private readonly PriorityAssessment $humanDonation,
        private readonly PriorityAssessment $animalFeed,
        private readonly PriorityAssessment $transformation,
        private readonly PriorityAssessment $composting,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function criticalPointId(): CriticalPointId
    {
        return $this->criticalPointId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function propertyId(): PropertyId
    {
        return $this->propertyId;
    }

    public function supplyChainStage(): SupplyChainStage
    {
        return $this->supplyChainStage;
    }

    public function prevention(): PriorityAssessment
    {
        return $this->prevention;
    }

    public function humanDonation(): PriorityAssessment
    {
        return $this->humanDonation;
    }

    public function animalFeed(): PriorityAssessment
    {
        return $this->animalFeed;
    }

    public function transformation(): PriorityAssessment
    {
        return $this->transformation;
    }

    public function composting(): PriorityAssessment
    {
        return $this->composting;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->criticalPointId;
    }

    public function payload(): array
    {
        return [
            'criticalPointId' => (string) $this->criticalPointId,
            'name' => $this->name,
            'propertyId' => (string) $this->propertyId,
            'supplyChainStage' => $this->supplyChainStage->value,
            'preventionPossible' => $this->prevention->isPossible,
            'humanDonationPossible' => $this->humanDonation->isPossible,
            'animalFeedPossible' => $this->animalFeed->isPossible,
            'transformationPossible' => $this->transformation->isPossible,
            'compostingPossible' => $this->composting->isPossible,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new CriticalPointUpdated(
            criticalPointId: new CriticalPointId($payload['criticalPointId']),
            name: $payload['name'],
            propertyId: new PropertyId($payload['propertyId']),
            supplyChainStage: SupplyChainStage::from($payload['supplyChainStage']),
            prevention: new PriorityAssessment(Priority::Prevention, (bool) ($payload['preventionPossible'] ?? false)),
            humanDonation: new PriorityAssessment(Priority::HumanDonation, (bool) ($payload['humanDonationPossible'] ?? false)),
            animalFeed: new PriorityAssessment(Priority::AnimalFeed, (bool) ($payload['animalFeedPossible'] ?? false)),
            transformation: new PriorityAssessment(Priority::Transformation, (bool) ($payload['transformationPossible'] ?? false)),
            composting: new PriorityAssessment(Priority::Composting, (bool) ($payload['compostingPossible'] ?? false)),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
