<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Surplus;

use App\FoodLoss\Domain\DomainEvent;
use App\FoodLoss\Domain\Model\Action\ActionId;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogUnit;
use App\FoodLoss\Domain\Model\Reason\ReasonId;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class SurplusUpdated extends DomainEvent
{
    protected static string $eventName = 'surplusUpdated';

    public function __construct(
        private readonly SurplusId $surplusId,
        private readonly \DateTimeImmutable $date,
        private readonly ServiceCategoryId $serviceCategoryId,
        private readonly CriticalPointId $criticalPointId,
        private readonly SurplusCauseId $surplusCauseId,
        private readonly FoodType $foodType,
        private readonly float $quantity,
        private readonly DeliveryLogUnit $unit,
        private readonly PreventionAssessment $preventionAssessment,
        private readonly TransformationAssessment $transformationAssessment,
        private readonly HumanDonationAssessment $humanDonationAssessment,
        private readonly AnimalFeedAssessment $animalFeedAssessment,
        private readonly CompostingAssessment $compostingAssessment,
        private readonly EliminationAssessment $eliminationAssessment,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function surplusId(): SurplusId
    {
        return $this->surplusId;
    }

    public function date(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function serviceCategoryId(): ServiceCategoryId
    {
        return $this->serviceCategoryId;
    }

    public function criticalPointId(): CriticalPointId
    {
        return $this->criticalPointId;
    }

    public function surplusCauseId(): SurplusCauseId
    {
        return $this->surplusCauseId;
    }

    public function foodType(): FoodType
    {
        return $this->foodType;
    }

    public function quantity(): float
    {
        return $this->quantity;
    }

    public function unit(): DeliveryLogUnit
    {
        return $this->unit;
    }

    public function preventionAssessment(): PreventionAssessment
    {
        return $this->preventionAssessment;
    }

    public function transformationAssessment(): TransformationAssessment
    {
        return $this->transformationAssessment;
    }

    public function humanDonationAssessment(): HumanDonationAssessment
    {
        return $this->humanDonationAssessment;
    }

    public function animalFeedAssessment(): AnimalFeedAssessment
    {
        return $this->animalFeedAssessment;
    }

    public function compostingAssessment(): CompostingAssessment
    {
        return $this->compostingAssessment;
    }

    public function eliminationAssessment(): EliminationAssessment
    {
        return $this->eliminationAssessment;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->surplusId;
    }

    public function payload(): array
    {
        return [
            'surplusId' => (string) $this->surplusId,
            'date' => $this->date->format(\DateTimeInterface::ATOM),
            'serviceCategoryId' => (string) $this->serviceCategoryId,
            'criticalPointId' => (string) $this->criticalPointId,
            'surplusCauseId' => (string) $this->surplusCauseId,
            'foodType' => $this->foodType->value,
            'quantity' => $this->quantity,
            'unit' => $this->unit->value,
            'preventionCanBePrevented' => $this->preventionAssessment->canBePrevented(),
            'preventionActionId' => null !== $this->preventionAssessment->actionId() ? (string) $this->preventionAssessment->actionId() : null,
            'transformationCanBeTransformed' => $this->transformationAssessment->canBeTransformed(),
            'transformationReasonId' => null !== $this->transformationAssessment->reasonId() ? (string) $this->transformationAssessment->reasonId() : null,
            'humanDonationCanBeHumanDonated' => $this->humanDonationAssessment->canBeHumanDonated(),
            'humanDonationReasonId' => null !== $this->humanDonationAssessment->reasonId() ? (string) $this->humanDonationAssessment->reasonId() : null,
            'animalFeedCanBeAnimalFed' => $this->animalFeedAssessment->canBeAnimalFed(),
            'animalFeedReasonId' => null !== $this->animalFeedAssessment->reasonId() ? (string) $this->animalFeedAssessment->reasonId() : null,
            'compostingCanBeComposted' => $this->compostingAssessment->canBeComposted(),
            'compostingReasonId' => null !== $this->compostingAssessment->reasonId() ? (string) $this->compostingAssessment->reasonId() : null,
            'eliminationCanBeEliminated' => $this->eliminationAssessment->canBeEliminated(),
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new SurplusUpdated(
            surplusId: new SurplusId($payload['surplusId']),
            date: new \DateTimeImmutable($payload['date']),
            serviceCategoryId: new ServiceCategoryId($payload['serviceCategoryId']),
            criticalPointId: new CriticalPointId($payload['criticalPointId']),
            surplusCauseId: new SurplusCauseId($payload['surplusCauseId']),
            foodType: FoodType::from($payload['foodType']),
            quantity: (float) $payload['quantity'],
            unit: DeliveryLogUnit::from($payload['unit']),
            preventionAssessment: new PreventionAssessment(
                canBePrevented: (bool) $payload['preventionCanBePrevented'],
                actionId: isset($payload['preventionActionId']) ? new ActionId((string) $payload['preventionActionId']) : null,
            ),
            transformationAssessment: new TransformationAssessment(
                canBeTransformed: (bool) $payload['transformationCanBeTransformed'],
                reasonId: isset($payload['transformationReasonId']) ? new ReasonId((string) $payload['transformationReasonId']) : null,
            ),
            humanDonationAssessment: new HumanDonationAssessment(
                canBeHumanDonated: (bool) $payload['humanDonationCanBeHumanDonated'],
                reasonId: isset($payload['humanDonationReasonId']) ? new ReasonId((string) $payload['humanDonationReasonId']) : null,
            ),
            animalFeedAssessment: new AnimalFeedAssessment(
                canBeAnimalFed: (bool) $payload['animalFeedCanBeAnimalFed'],
                reasonId: isset($payload['animalFeedReasonId']) ? new ReasonId((string) $payload['animalFeedReasonId']) : null,
            ),
            compostingAssessment: new CompostingAssessment(
                canBeComposted: (bool) $payload['compostingCanBeComposted'],
                reasonId: isset($payload['compostingReasonId']) ? new ReasonId((string) $payload['compostingReasonId']) : null,
            ),
            eliminationAssessment: new EliminationAssessment(
                canBeEliminated: (bool) $payload['eliminationCanBeEliminated'],
            ),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
