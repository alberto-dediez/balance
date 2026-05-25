<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Surplus;

use App\FoodLoss\Domain\Model\Action\ActionId;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogUnit;
use App\FoodLoss\Domain\Model\Reason\ReasonId;
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

    public function assessPrevention(bool $canBePrevented, ?ActionId $actionId): void
    {
        $this->recordApplyAndPublish(
            new SurplusPreventionAssessed(
                surplusId: $this->id(),
                canBePrevented: $canBePrevented,
                actionId: $actionId,
            ),
        );
    }

    public function assessTransformation(
        bool $canBeTransformed,
        ?ReasonId $reasonId,
        ?TransformationProduct $product = null,
        ?TransformationChannel $channel = null,
    ): void {
        $this->recordApplyAndPublish(
            new SurplusTransformationAssessed(
                surplusId: $this->id(),
                canBeTransformed: $canBeTransformed,
                reasonId: $reasonId,
                product: $product,
                channel: $channel,
            ),
        );
    }

    public function assessHumanDonation(bool $canBeHumanDonated, ?ReasonId $reasonId): void
    {
        $this->recordApplyAndPublish(
            new SurplusHumanDonationAssessed(
                surplusId: $this->id(),
                canBeHumanDonated: $canBeHumanDonated,
                reasonId: $reasonId,
            ),
        );
    }

    public function assessAnimalFeed(bool $canBeAnimalFed, ?ReasonId $reasonId): void
    {
        $this->recordApplyAndPublish(
            new SurplusAnimalFeedAssessed(
                surplusId: $this->id(),
                canBeAnimalFed: $canBeAnimalFed,
                reasonId: $reasonId,
            ),
        );
    }

    public function assessComposting(bool $canBeComposted, ?ReasonId $reasonId): void
    {
        $this->recordApplyAndPublish(
            new SurplusCompostingAssessed(
                surplusId: $this->id(),
                canBeComposted: $canBeComposted,
                reasonId: $reasonId,
            ),
        );
    }

    public function assessElimination(bool $canBeEliminated): void
    {
        $this->recordApplyAndPublish(
            new SurplusEliminationAssessed(
                surplusId: $this->id(),
                canBeEliminated: $canBeEliminated,
            ),
        );
    }

    public function applySurplusPreventionAssessed(SurplusPreventionAssessed $event): void
    {
        $this->preventionAssessment = new PreventionAssessment(
            canBePrevented: $event->canBePrevented(),
            actionId: $event->actionId(),
        );
        $this->updatedAt = $event->occurredOn();
    }

    public function applySurplusTransformationAssessed(SurplusTransformationAssessed $event): void
    {
        $this->transformationAssessment = new TransformationAssessment(
            canBeTransformed: $event->canBeTransformed(),
            reasonId: $event->reasonId(),
            product: $event->product(),
            channel: $event->channel(),
        );

        if ($event->canBeTransformed()) {
            $this->destination = SurplusDestination::Transformation;
            $this->humanDonationAssessment = new HumanDonationAssessment();
            $this->animalFeedAssessment = new AnimalFeedAssessment();
            $this->compostingAssessment = new CompostingAssessment();
            $this->eliminationAssessment = new EliminationAssessment();
        } elseif (SurplusDestination::Transformation === $this->destination) {
            $this->destination = null;
        }

        $this->updatedAt = $event->occurredOn();
    }

    public function applySurplusHumanDonationAssessed(SurplusHumanDonationAssessed $event): void
    {
        $this->humanDonationAssessment = new HumanDonationAssessment(
            canBeHumanDonated: $event->canBeHumanDonated(),
            reasonId: $event->reasonId(),
        );

        if ($event->canBeHumanDonated()) {
            $this->destination = SurplusDestination::HumanDonation;
            $this->animalFeedAssessment = new AnimalFeedAssessment();
            $this->compostingAssessment = new CompostingAssessment();
            $this->eliminationAssessment = new EliminationAssessment();
        } elseif (SurplusDestination::HumanDonation === $this->destination) {
            $this->destination = null;
        }

        $this->updatedAt = $event->occurredOn();
    }

    public function applySurplusAnimalFeedAssessed(SurplusAnimalFeedAssessed $event): void
    {
        $this->animalFeedAssessment = new AnimalFeedAssessment(
            canBeAnimalFed: $event->canBeAnimalFed(),
            reasonId: $event->reasonId(),
        );

        if ($event->canBeAnimalFed()) {
            $this->destination = SurplusDestination::AnimalFeed;
            $this->compostingAssessment = new CompostingAssessment();
            $this->eliminationAssessment = new EliminationAssessment();
        } elseif (SurplusDestination::AnimalFeed === $this->destination) {
            $this->destination = null;
        }

        $this->updatedAt = $event->occurredOn();
    }

    public function applySurplusCompostingAssessed(SurplusCompostingAssessed $event): void
    {
        $this->compostingAssessment = new CompostingAssessment(
            canBeComposted: $event->canBeComposted(),
            reasonId: $event->reasonId(),
        );

        if ($event->canBeComposted()) {
            $this->destination = SurplusDestination::Composting;
            $this->eliminationAssessment = new EliminationAssessment();
        } elseif (SurplusDestination::Composting === $this->destination) {
            $this->destination = null;
        }

        $this->updatedAt = $event->occurredOn();
    }

    public function applySurplusEliminationAssessed(SurplusEliminationAssessed $event): void
    {
        $this->eliminationAssessment = new EliminationAssessment(
            canBeEliminated: $event->canBeEliminated(),
        );

        if ($event->canBeEliminated()) {
            $this->destination = SurplusDestination::Elimination;
        } elseif (SurplusDestination::Elimination === $this->destination) {
            $this->destination = null;
        }

        $this->updatedAt = $event->occurredOn();
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
        $this->updatedAt = $event->occurredOn();
    }

    public function applySurplusRemoved(SurplusRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return  $this->date->format('d/m/Y');
    }
}
