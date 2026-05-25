<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Surplus;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogUnit;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;

class Surplus implements \Stringable
{
    use AggregateRoot;

    /** @param array<DomainEvent> $events */
    public function __construct(
        private SurplusId $id,
        private \DateTimeImmutable $date = new \DateTimeImmutable('NOW'),
        private ServiceCategoryId $serviceCategoryId = new ServiceCategoryId('00000000-0000-0000-0000-000000000000'),
        private CriticalPointId $criticalPointId = new CriticalPointId('00000000-0000-0000-0000-000000000000'),
        private SurplusCauseId $surplusCauseId = new SurplusCauseId('00000000-0000-0000-0000-000000000000'),
        private FoodType $foodType = FoodType::Processed,
        private float $quantity = 0.0,
        private DeliveryLogUnit $unit = DeliveryLogUnit::Kg,
        private AccountId $accountId = new AccountId('00000000-0000-0000-0000-000000000000'),
        private UserId $registeredBy = new UserId('00000000-0000-0000-0000-000000000000'),
        private PreventionAssessment $preventionAssessment = new PreventionAssessment(),
        private TransformationAssessment $transformationAssessment = new TransformationAssessment(),
        private HumanDonationAssessment $humanDonationAssessment = new HumanDonationAssessment(),
        private AnimalFeedAssessment $animalFeedAssessment = new AnimalFeedAssessment(),
        private CompostingAssessment $compostingAssessment = new CompostingAssessment(),
        private EliminationAssessment $eliminationAssessment = new EliminationAssessment(),
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
        private ?SurplusDestination $destination = null,
    ) {
        $this->events = $events;
    }

    public function id(): SurplusId
    {
        return $this->id;
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

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function registeredBy(): UserId
    {
        return $this->registeredBy;
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

    public function isRemoved(): bool
    {
        return $this->removed;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function removedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }

    public function destination(): ?SurplusDestination
    {
        return $this->destination;
    }

    public function register(
        \DateTimeImmutable $date,
        ServiceCategoryId $serviceCategoryId,
        CriticalPointId $criticalPointId,
        SurplusCauseId $surplusCauseId,
        FoodType $foodType,
        float $quantity,
        DeliveryLogUnit $unit,
        AccountId $accountId,
        UserId $registeredBy,
        PreventionAssessment $preventionAssessment,
        TransformationAssessment $transformationAssessment,
        HumanDonationAssessment $humanDonationAssessment,
        AnimalFeedAssessment $animalFeedAssessment,
        CompostingAssessment $compostingAssessment,
        EliminationAssessment $eliminationAssessment,
    ): void {
        $this->recordApplyAndPublish(
            new SurplusRegistered(
                surplusId: $this->id(),
                date: $date,
                serviceCategoryId: $serviceCategoryId,
                criticalPointId: $criticalPointId,
                surplusCauseId: $surplusCauseId,
                foodType: $foodType,
                quantity: $quantity,
                unit: $unit,
                accountId: $accountId,
                registeredBy: $registeredBy,
                preventionAssessment: $preventionAssessment,
                transformationAssessment: $transformationAssessment,
                humanDonationAssessment: $humanDonationAssessment,
                animalFeedAssessment: $animalFeedAssessment,
                compostingAssessment: $compostingAssessment,
                eliminationAssessment: $eliminationAssessment,
            ),
        );
    }

    public function update(
        \DateTimeImmutable $date,
        ServiceCategoryId $serviceCategoryId,
        CriticalPointId $criticalPointId,
        SurplusCauseId $surplusCauseId,
        FoodType $foodType,
        float $quantity,
        DeliveryLogUnit $unit,
        PreventionAssessment $preventionAssessment,
        TransformationAssessment $transformationAssessment,
        HumanDonationAssessment $humanDonationAssessment,
        AnimalFeedAssessment $animalFeedAssessment,
        CompostingAssessment $compostingAssessment,
        EliminationAssessment $eliminationAssessment,
    ): void {
        $this->recordApplyAndPublish(
            new SurplusUpdated(
                surplusId: $this->id(),
                date: $date,
                serviceCategoryId: $serviceCategoryId,
                criticalPointId: $criticalPointId,
                surplusCauseId: $surplusCauseId,
                foodType: $foodType,
                quantity: $quantity,
                unit: $unit,
                preventionAssessment: $preventionAssessment,
                transformationAssessment: $transformationAssessment,
                humanDonationAssessment: $humanDonationAssessment,
                animalFeedAssessment: $animalFeedAssessment,
                compostingAssessment: $compostingAssessment,
                eliminationAssessment: $eliminationAssessment,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new SurplusRemoved(
                surplusId: $this->id(),
            ),
        );
    }

    public function transformationAssess(): void
    {
        $this->recordApplyAndPublish(
            new SurplusAssessed(
                surplusId: $this->id(),
                destination: SurplusDestination::Transformation,
            ),
        );
    }

    public function humanDonationAssess(): void
    {
        $this->recordApplyAndPublish(
            new SurplusAssessed(
                surplusId: $this->id(),
                destination: SurplusDestination::HumanDonation,
            ),
        );
    }

    public function animalFeedAssess(): void
    {
        $this->recordApplyAndPublish(
            new SurplusAssessed(
                surplusId: $this->id(),
                destination: SurplusDestination::AnimalFeed,
            ),
        );
    }

    public function compostingAssess(): void
    {
        $this->recordApplyAndPublish(
            new SurplusAssessed(
                surplusId: $this->id(),
                destination: SurplusDestination::Composting,
            ),
        );
    }

    public function eliminationAssess(): void
    {
        $this->recordApplyAndPublish(
            new SurplusAssessed(
                surplusId: $this->id(),
                destination: SurplusDestination::Elimination,
            ),
        );
    }

    public function applySurplusAssessed(SurplusAssessed $event): void
    {
        $this->destination = $event->destination();
        $this->updatedAt = $event->occurredOn();

        match ($event->destination()) {
            SurplusDestination::Transformation => $this->applyTransformationAssessedState(),
            SurplusDestination::HumanDonation => $this->applyHumanDonationAssessedState(),
            SurplusDestination::AnimalFeed => $this->applyAnimalFeedAssessedState(),
            SurplusDestination::Composting => $this->applyCompostingAssessedState(),
            SurplusDestination::Elimination => $this->applyEliminationAssessedState(),
        };
    }

    private function applyTransformationAssessedState(): void
    {
        $this->transformationAssessment = new TransformationAssessment(canBeTransformed: true, reasonId: null);
        $this->humanDonationAssessment = new HumanDonationAssessment();
        $this->animalFeedAssessment = new AnimalFeedAssessment();
        $this->compostingAssessment = new CompostingAssessment();
        $this->eliminationAssessment = new EliminationAssessment();
    }

    private function applyHumanDonationAssessedState(): void
    {
        $this->humanDonationAssessment = new HumanDonationAssessment(canBeHumanDonated: true, reasonId: null);
        $this->animalFeedAssessment = new AnimalFeedAssessment();
        $this->compostingAssessment = new CompostingAssessment();
        $this->eliminationAssessment = new EliminationAssessment();
    }

    private function applyAnimalFeedAssessedState(): void
    {
        $this->animalFeedAssessment = new AnimalFeedAssessment(canBeAnimalFed: true, reasonId: null);
        $this->compostingAssessment = new CompostingAssessment();
        $this->eliminationAssessment = new EliminationAssessment();
    }

    private function applyCompostingAssessedState(): void
    {
        $this->compostingAssessment = new CompostingAssessment(canBeComposted: true, reasonId: null);
        $this->eliminationAssessment = new EliminationAssessment();
    }

    private function applyEliminationAssessedState(): void
    {
        $this->eliminationAssessment = new EliminationAssessment(canBeEliminated: true);
    }

    public function applySurplusRegistered(SurplusRegistered $event): void
    {
        $this->date = $event->date();
        $this->serviceCategoryId = $event->serviceCategoryId();
        $this->criticalPointId = $event->criticalPointId();
        $this->surplusCauseId = $event->surplusCauseId();
        $this->foodType = $event->foodType();
        $this->quantity = $event->quantity();
        $this->unit = $event->unit();
        $this->accountId = $event->accountId();
        $this->registeredBy = $event->registeredBy();
        $this->preventionAssessment = $event->preventionAssessment();
        $this->transformationAssessment = $event->transformationAssessment();
        $this->humanDonationAssessment = $event->humanDonationAssessment();
        $this->animalFeedAssessment = $event->animalFeedAssessment();
        $this->compostingAssessment = $event->compostingAssessment();
        $this->eliminationAssessment = $event->eliminationAssessment();
        $this->registeredAt = $event->occurredOn();
    }

    public function applySurplusUpdated(SurplusUpdated $event): void
    {
        $this->date = $event->date();
        $this->serviceCategoryId = $event->serviceCategoryId();
        $this->criticalPointId = $event->criticalPointId();
        $this->surplusCauseId = $event->surplusCauseId();
        $this->foodType = $event->foodType();
        $this->quantity = $event->quantity();
        $this->unit = $event->unit();
        $this->preventionAssessment = $event->preventionAssessment();
        $this->transformationAssessment = $event->transformationAssessment();
        $this->humanDonationAssessment = $event->humanDonationAssessment();
        $this->animalFeedAssessment = $event->animalFeedAssessment();
        $this->compostingAssessment = $event->compostingAssessment();
        $this->eliminationAssessment = $event->eliminationAssessment();
        $this->updatedAt = $event->occurredOn();
    }

    public function applySurplusRemoved(SurplusRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return sprintf('%s - %s', $this->date->format('Y-m-d'), $this->foodType->value);
    }
}
