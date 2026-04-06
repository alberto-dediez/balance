<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\CriticalPoint;

use App\Shared\Domain\Event\DomainEvent;
use App\FoodLoss\Domain\Model\Action\ActionId;
use App\FoodLoss\Domain\Model\Action\Priority;
use App\FoodLoss\Domain\Model\Reason\ReasonId;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\AggregateRoot;

class CriticalPoint implements \Stringable
{
    use AggregateRoot;

    private bool $preventionPossible = false;
    private bool $humanDonationPossible = false;
    private bool $animalFeedPossible = false;
    private bool $transformationPossible = false;
    private bool $compostingPossible = false;

    /** @param array<DomainEvent> $events */
    public function __construct(
        private CriticalPointId $id,
        private string $name = '',
        private ?PropertyId $propertyId = null,
        private ?AccountId $accountId = null,
        private SupplyChainStage $supplyChainStage = SupplyChainStage::Receiving,
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
        /** @var iterable<CriticalPointServiceCategoryLink>|CriticalPointServiceCategories */
        private iterable $serviceCategories = [],
        /** @var iterable<CriticalPointActionLink>|CriticalPointActions */
        private iterable $actions = [],
        /** @var iterable<CriticalPointReasonLink>|CriticalPointReasons */
        private iterable $reasons = [],
    ) {
        $this->events = $events;
    }

    public function id(): CriticalPointId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function propertyId(): ?PropertyId
    {
        return $this->propertyId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function supplyChainStage(): SupplyChainStage
    {
        return $this->supplyChainStage;
    }

    public function prevention(): PriorityAssessment
    {
        return new PriorityAssessment(
            priority: Priority::Prevention,
            isPossible: $this->preventionPossible,
            actionIds: $this->actions()->byPriority(Priority::Prevention),
            reasonIds: $this->reasons()->byPriority(Priority::Prevention),
        );
    }

    public function humanDonation(): PriorityAssessment
    {
        return new PriorityAssessment(
            priority: Priority::HumanDonation,
            isPossible: $this->humanDonationPossible,
            actionIds: $this->actions()->byPriority(Priority::HumanDonation),
            reasonIds: $this->reasons()->byPriority(Priority::HumanDonation),
        );
    }

    public function animalFeed(): PriorityAssessment
    {
        return new PriorityAssessment(
            priority: Priority::AnimalFeed,
            isPossible: $this->animalFeedPossible,
            actionIds: $this->actions()->byPriority(Priority::AnimalFeed),
            reasonIds: $this->reasons()->byPriority(Priority::AnimalFeed),
        );
    }

    public function transformation(): PriorityAssessment
    {
        return new PriorityAssessment(
            priority: Priority::Transformation,
            isPossible: $this->transformationPossible,
            actionIds: $this->actions()->byPriority(Priority::Transformation),
            reasonIds: $this->reasons()->byPriority(Priority::Transformation),
        );
    }

    public function composting(): PriorityAssessment
    {
        return new PriorityAssessment(
            priority: Priority::Composting,
            isPossible: $this->compostingPossible,
            actionIds: $this->actions()->byPriority(Priority::Composting),
            reasonIds: $this->reasons()->byPriority(Priority::Composting),
        );
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

    /** @param ServiceCategoryId[] $serviceCategoryIds */
    public function register(
        string $name,
        PropertyId $propertyId,
        AccountId $accountId,
        SupplyChainStage $supplyChainStage,
        PriorityAssessment $prevention,
        PriorityAssessment $humanDonation,
        PriorityAssessment $animalFeed,
        PriorityAssessment $transformation,
        PriorityAssessment $composting,
        array $serviceCategoryIds = [],
    ): void {
        $this->recordApplyAndPublish(
            new CriticalPointRegistered(
                criticalPointId: $this->id(),
                name: $name,
                propertyId: $propertyId,
                accountId: $accountId,
                supplyChainStage: $supplyChainStage,
                prevention: $prevention,
                humanDonation: $humanDonation,
                animalFeed: $animalFeed,
                transformation: $transformation,
                composting: $composting,
            ),
        );

        $this->syncServiceCategories($serviceCategoryIds);
        $this->syncActionsFromAssessments($prevention, $humanDonation, $animalFeed, $transformation, $composting);
        $this->syncReasonsFromAssessments($prevention, $humanDonation, $animalFeed, $transformation, $composting);
    }

    /** @param ServiceCategoryId[] $serviceCategoryIds */
    public function update(
        string $name,
        PropertyId $propertyId,
        SupplyChainStage $supplyChainStage,
        PriorityAssessment $prevention,
        PriorityAssessment $humanDonation,
        PriorityAssessment $animalFeed,
        PriorityAssessment $transformation,
        PriorityAssessment $composting,
        array $serviceCategoryIds = [],
    ): void {
        $this->recordApplyAndPublish(
            new CriticalPointUpdated(
                criticalPointId: $this->id(),
                name: $name,
                propertyId: $propertyId,
                supplyChainStage: $supplyChainStage,
                prevention: $prevention,
                humanDonation: $humanDonation,
                animalFeed: $animalFeed,
                transformation: $transformation,
                composting: $composting,
            ),
        );

        $this->syncServiceCategories($serviceCategoryIds);
        $this->syncActionsFromAssessments($prevention, $humanDonation, $animalFeed, $transformation, $composting);
        $this->syncReasonsFromAssessments($prevention, $humanDonation, $animalFeed, $transformation, $composting);
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new CriticalPointRemoved(
                criticalPointId: $this->id(),
            ),
        );
    }

    public function serviceCategories(): CriticalPointServiceCategories
    {
        if ($this->serviceCategories instanceof CriticalPointServiceCategories) {
            return $this->serviceCategories;
        }

        $this->serviceCategories = new CriticalPointServiceCategories(
            array_values(iterator_to_array($this->serviceCategories)),
        );

        return $this->serviceCategories;
    }

    /**
     * @return ServiceCategoryId[]
     */
    public function serviceCategoryIds(): array
    {
        return $this->serviceCategories()->ids();
    }

    public function linkServiceCategory(ServiceCategoryId $serviceCategoryId): void
    {
        if ($this->serviceCategories()->has($serviceCategoryId)) {
            return;
        }

        $this->recordApplyAndPublish(
            new CriticalPointServiceCategoryLinked(
                criticalPointId: $this->id(),
                serviceCategoryId: $serviceCategoryId,
            ),
        );
    }

    public function unlinkServiceCategory(ServiceCategoryId $serviceCategoryId): void
    {
        if (!$this->serviceCategories()->has($serviceCategoryId)) {
            return;
        }

        $this->recordApplyAndPublish(
            new CriticalPointServiceCategoryUnlinked(
                criticalPointId: $this->id(),
                serviceCategoryId: $serviceCategoryId,
            ),
        );
    }

    /**
     * @param ServiceCategoryId[] $newServiceCategoryIds
     */
    private function syncServiceCategories(array $newServiceCategoryIds): void
    {
        $current = $this->serviceCategories();

        // Unlink categories that are no longer present
        foreach ($current->ids() as $currentId) {
            $found = false;
            foreach ($newServiceCategoryIds as $newId) {
                if ($currentId->equals($newId)) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $this->unlinkServiceCategory($currentId);
            }
        }

        // Link new categories
        foreach ($newServiceCategoryIds as $newId) {
            if (!$current->has($newId)) {
                $this->linkServiceCategory($newId);
            }
        }
    }

    public function actions(): CriticalPointActions
    {
        if ($this->actions instanceof CriticalPointActions) {
            return $this->actions;
        }

        $this->actions = new CriticalPointActions(
            array_values(iterator_to_array($this->actions)),
        );

        return $this->actions;
    }

    /**
     * @return ActionId[]
     */
    public function actionIds(): array
    {
        return $this->actions()->ids();
    }

    public function linkAction(ActionId $actionId, Priority $priority): void
    {
        if ($this->actions()->has($actionId)) {
            return;
        }

        $this->recordApplyAndPublish(
            new CriticalPointActionLinked(
                criticalPointId: $this->id(),
                actionId: $actionId,
                priority: $priority,
            ),
        );
    }

    public function unlinkAction(ActionId $actionId): void
    {
        if (!$this->actions()->has($actionId)) {
            return;
        }

        $this->recordApplyAndPublish(
            new CriticalPointActionUnlinked(
                criticalPointId: $this->id(),
                actionId: $actionId,
            ),
        );
    }

    private function syncActionsFromAssessments(
        PriorityAssessment $prevention,
        PriorityAssessment $humanDonation,
        PriorityAssessment $animalFeed,
        PriorityAssessment $transformation,
        PriorityAssessment $composting,
    ): void {
        /** @var array<string, array{ActionId, Priority}> $newActions */
        $newActions = [];
        foreach ([$prevention, $humanDonation, $animalFeed, $transformation, $composting] as $assessment) {
            foreach ($assessment->actionIds as $actionId) {
                $newActions[(string) $actionId] = [$actionId, $assessment->priority];
            }
        }

        $current = $this->actions();

        foreach ($current->ids() as $currentId) {
            if (!array_key_exists((string) $currentId, $newActions)) {
                $this->unlinkAction($currentId);
            }
        }

        foreach ($newActions as [$actionId, $priority]) {
            if (!$current->has($actionId)) {
                $this->linkAction($actionId, $priority);
            }
        }
    }

    public function reasons(): CriticalPointReasons
    {
        if ($this->reasons instanceof CriticalPointReasons) {
            return $this->reasons;
        }

        $this->reasons = new CriticalPointReasons(
            array_values(iterator_to_array($this->reasons)),
        );

        return $this->reasons;
    }

    /**
     * @return ReasonId[]
     */
    public function reasonIds(): array
    {
        return $this->reasons()->ids();
    }

    public function linkReason(ReasonId $reasonId, Priority $priority): void
    {
        if ($this->reasons()->has($reasonId)) {
            return;
        }

        $this->recordApplyAndPublish(
            new CriticalPointReasonLinked(
                criticalPointId: $this->id(),
                reasonId: $reasonId,
                priority: $priority,
            ),
        );
    }

    public function unlinkReason(ReasonId $reasonId): void
    {
        if (!$this->reasons()->has($reasonId)) {
            return;
        }

        $this->recordApplyAndPublish(
            new CriticalPointReasonUnlinked(
                criticalPointId: $this->id(),
                reasonId: $reasonId,
            ),
        );
    }

    private function syncReasonsFromAssessments(
        PriorityAssessment $prevention,
        PriorityAssessment $humanDonation,
        PriorityAssessment $animalFeed,
        PriorityAssessment $transformation,
        PriorityAssessment $composting,
    ): void {
        /** @var array<string, array{ReasonId, Priority}> $newReasons */
        $newReasons = [];
        foreach ([$prevention, $humanDonation, $animalFeed, $transformation, $composting] as $assessment) {
            foreach ($assessment->reasonIds as $reasonId) {
                $newReasons[(string) $reasonId] = [$reasonId, $assessment->priority];
            }
        }

        $current = $this->reasons();

        foreach ($current->ids() as $currentId) {
            if (!array_key_exists((string) $currentId, $newReasons)) {
                $this->unlinkReason($currentId);
            }
        }

        foreach ($newReasons as [$reasonId, $priority]) {
            if (!$current->has($reasonId)) {
                $this->linkReason($reasonId, $priority);
            }
        }
    }

    public function applyCriticalPointRegistered(CriticalPointRegistered $event): void
    {
        $this->name = $event->name();
        $this->propertyId = $event->propertyId();
        $this->accountId = $event->accountId();
        $this->supplyChainStage = $event->supplyChainStage();
        $this->preventionPossible = $event->prevention()->isPossible;
        $this->humanDonationPossible = $event->humanDonation()->isPossible;
        $this->animalFeedPossible = $event->animalFeed()->isPossible;
        $this->transformationPossible = $event->transformation()->isPossible;
        $this->compostingPossible = $event->composting()->isPossible;
        $this->registeredAt = $event->occurredOn();
    }

    public function applyCriticalPointUpdated(CriticalPointUpdated $event): void
    {
        $this->name = $event->name();
        $this->propertyId = $event->propertyId();
        $this->supplyChainStage = $event->supplyChainStage();
        $this->preventionPossible = $event->prevention()->isPossible;
        $this->humanDonationPossible = $event->humanDonation()->isPossible;
        $this->animalFeedPossible = $event->animalFeed()->isPossible;
        $this->transformationPossible = $event->transformation()->isPossible;
        $this->compostingPossible = $event->composting()->isPossible;
        $this->updatedAt = $event->occurredOn();
    }

    public function applyCriticalPointRemoved(CriticalPointRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function applyCriticalPointServiceCategoryLinked(CriticalPointServiceCategoryLinked $event): void
    {
        $link = new CriticalPointServiceCategoryLink(
            criticalPoint: $this,
            serviceCategoryId: $event->serviceCategoryId(),
        );

        $this->serviceCategories()->add($link);
    }

    public function applyCriticalPointServiceCategoryUnlinked(CriticalPointServiceCategoryUnlinked $event): void
    {
        $this->serviceCategories()->removeById($event->serviceCategoryId());
    }

    public function applyCriticalPointActionLinked(CriticalPointActionLinked $event): void
    {
        $link = new CriticalPointActionLink(
            criticalPoint: $this,
            actionId: $event->actionId(),
            priority: $event->priority(),
        );

        $this->actions()->add($link);
    }

    public function applyCriticalPointActionUnlinked(CriticalPointActionUnlinked $event): void
    {
        $this->actions()->removeById($event->actionId());
    }

    public function applyCriticalPointReasonLinked(CriticalPointReasonLinked $event): void
    {
        $link = new CriticalPointReasonLink(
            criticalPoint: $this,
            reasonId: $event->reasonId(),
            priority: $event->priority(),
        );

        $this->reasons()->add($link);
    }

    public function applyCriticalPointReasonUnlinked(CriticalPointReasonUnlinked $event): void
    {
        $this->reasons()->removeById($event->reasonId());
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
