<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\CriticalPoint;

use App\FoodLoss\Domain\DomainEvent;
use App\FoodLoss\Domain\Model\Action\Priority;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CriticalPointRegistered extends DomainEvent
{
    protected static string $eventName = 'criticalPointRegistered';

    public function __construct(
        private CriticalPointId $criticalPointId,
        private string $name,
        private PropertyId $propertyId,
        private AccountId $accountId,
        private SupplyChainStage $supplyChainStage,
        private PriorityAssessment $prevention,
        private PriorityAssessment $humanDonation,
        private PriorityAssessment $animalFeed,
        private PriorityAssessment $transformation,
        private PriorityAssessment $composting,
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

    public function accountId(): AccountId
    {
        return $this->accountId;
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
        return $this->criticalPointId();
    }

    public function payload(): array
    {
        return [
            'criticalPointId' => (string) $this->criticalPointId,
            'name' => $this->name,
            'propertyId' => (string) $this->propertyId,
            'accountId' => (string) $this->accountId,
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
        return new CriticalPointRegistered(
            criticalPointId: new CriticalPointId($payload['criticalPointId']),
            name: $payload['name'],
            propertyId: new PropertyId($payload['propertyId']),
            accountId: new AccountId($payload['accountId']),
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
