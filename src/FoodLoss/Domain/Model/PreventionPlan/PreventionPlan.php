<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\PreventionPlan;

use App\FoodLoss\Domain\Model\Action\ActionId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Model\AggregateRoot;

class PreventionPlan implements \Stringable
{
    use AggregateRoot;

    /** @var iterable<PreventionPlanPropertyLink>|PreventionPlanProperties */
    private iterable $properties = [];

    /** @var iterable<PreventionPlanVersion>|PreventionPlanVersions */
    private iterable $versions = [];

    private int $currentVersion = 0;

    /** @param array<\App\Shared\Domain\Event\DomainEvent> $events */
    public function __construct(
        private PreventionPlanId $id,
        private string $name = '',
        private ?AccountId $accountId = null,
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): PreventionPlanId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function properties(): PreventionPlanProperties
    {
        if ($this->properties instanceof PreventionPlanProperties) {
            return $this->properties;
        }

        $this->properties = new PreventionPlanProperties(
            array_values(iterator_to_array($this->properties)),
        );

        return $this->properties;
    }

    /** @return PropertyId[] */
    public function propertyIds(): array
    {
        return $this->properties()->ids();
    }

    public function versions(): PreventionPlanVersions
    {
        if ($this->versions instanceof PreventionPlanVersions) {
            return $this->versions;
        }

        $this->versions = new PreventionPlanVersions(
            array_values(iterator_to_array($this->versions)),
        );

        return $this->versions;
    }

    public function currentVersion(): int
    {
        return $this->currentVersion;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
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

    /**
     * @param PropertyId[] $propertyIds
     */
    public function register(
        string $name,
        array $propertyIds,
        AccountId $accountId,
        UserId $registeredBy,
    ): void {
        $this->recordApplyAndPublish(
            new PreventionPlanRegistered(
                preventionPlanId: $this->id(),
                name: $name,
                accountId: $accountId,
            ),
        );

        $this->recordApplyAndPublish(
            new PreventionPlanVersionCreated(
                preventionPlanId: $this->id(),
                versionNumber: 0,
                createdBy: $registeredBy,
            ),
        );

        $this->syncProperties($propertyIds);
    }

    /**
     * @param PropertyId[] $propertyIds
     */
    public function update(
        string $name,
        array $propertyIds,
        UserId $updatedBy,
    ): void {
        $propertiesChanged = $this->propertiesChanged($propertyIds);

        $this->recordApplyAndPublish(
            new PreventionPlanUpdated(
                preventionPlanId: $this->id(),
                name: $name,
            ),
        );

        if ($propertiesChanged) {
            $this->recordApplyAndPublish(
                new PreventionPlanVersionCreated(
                    preventionPlanId: $this->id(),
                    versionNumber: $this->currentVersion + 1,
                    createdBy: $updatedBy,
                ),
            );
        }

        $this->syncProperties($propertyIds);
    }

    public function linkProperty(PropertyId $propertyId): void
    {
        if ($this->properties()->has($propertyId)) {
            return;
        }

        $this->recordApplyAndPublish(
            new PreventionPlanPropertyLinked(
                preventionPlanId: $this->id(),
                propertyId: $propertyId,
            ),
        );
    }

    public function unlinkProperty(PropertyId $propertyId): void
    {
        if (!$this->properties()->has($propertyId)) {
            return;
        }

        $this->recordApplyAndPublish(
            new PreventionPlanPropertyUnlinked(
                preventionPlanId: $this->id(),
                propertyId: $propertyId,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new PreventionPlanRemoved(
                preventionPlanId: $this->id(),
            ),
        );
    }

    public function addProposedAction(
        PropertyId $propertyId,
        ActionId $actionId,
    ): void {
        $version = $this->versions()->findByVersionNumber($this->currentVersion);
        if (null === $version) {
            return;
        }

        if ($version->proposedActions()->has($propertyId, $actionId)) {
            return;
        }

        $this->recordApplyAndPublish(
            new ProposedActionAdded(
                preventionPlanId: $this->id(),
                versionNumber: $this->currentVersion,
                propertyId: $propertyId,
                actionId: $actionId,
            ),
        );
    }

    public function removeProposedAction(
        PropertyId $propertyId,
        ActionId $actionId,
    ): void {
        $version = $this->versions()->findByVersionNumber($this->currentVersion);
        if (null === $version) {
            return;
        }

        if (!$version->proposedActions()->has($propertyId, $actionId)) {
            return;
        }

        $this->recordApplyAndPublish(
            new ProposedActionRemoved(
                preventionPlanId: $this->id(),
                versionNumber: $this->currentVersion,
                propertyId: $propertyId,
                actionId: $actionId,
            ),
        );
    }

    public function applyPreventionPlanRegistered(PreventionPlanRegistered $event): void
    {
        $this->name = $event->name();
        $this->accountId = $event->accountId();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyPreventionPlanUpdated(PreventionPlanUpdated $event): void
    {
        $this->name = $event->name();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyPreventionPlanPropertyLinked(PreventionPlanPropertyLinked $event): void
    {
        $link = new PreventionPlanPropertyLink(
            preventionPlan: $this,
            propertyId: $event->propertyId(),
        );

        $this->properties()->add($link);
    }

    public function applyPreventionPlanPropertyUnlinked(PreventionPlanPropertyUnlinked $event): void
    {
        $this->properties()->removeById($event->propertyId());
    }

    public function applyPreventionPlanRemoved(PreventionPlanRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function applyPreventionPlanVersionCreated(PreventionPlanVersionCreated $event): void
    {
        $version = new PreventionPlanVersion(
            preventionPlan: $this,
            versionNumber: $event->versionNumber(),
            createdBy: $event->createdBy(),
            createdAt: $event->occurredOn(),
        );

        $this->versions()->add($version);
        $this->currentVersion = $event->versionNumber();
    }

    public function applyProposedActionAdded(ProposedActionAdded $event): void
    {
        $version = $this->versions()->findByVersionNumber($event->versionNumber());
        if (null === $version) {
            return;
        }

        $version->proposedActions()->add(
            new ProposedAction(
                preventionPlanVersion: $version,
                propertyId: $event->propertyId(),
                actionId: $event->actionId(),
            ),
        );
    }

    public function applyProposedActionRemoved(ProposedActionRemoved $event): void
    {
        $version = $this->versions()->findByVersionNumber($event->versionNumber());
        if (null === $version) {
            return;
        }

        $version->proposedActions()->removeByIds($event->propertyId(), $event->actionId());
    }

    /** @param PropertyId[] $newPropertyIds */
    private function propertiesChanged(array $newPropertyIds): bool
    {
        $current = $this->propertyIds();

        if (count($current) !== count($newPropertyIds)) {
            return true;
        }

        foreach ($current as $currentId) {
            $found = false;
            foreach ($newPropertyIds as $newId) {
                if ($currentId->equals($newId)) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                return true;
            }
        }

        return false;
    }

    /** @param PropertyId[] $newPropertyIds */
    private function syncProperties(array $newPropertyIds): void
    {
        $current = $this->properties();

        foreach ($current->ids() as $currentId) {
            $found = false;
            foreach ($newPropertyIds as $newId) {
                if ($currentId->equals($newId)) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $this->unlinkProperty($currentId);
            }
        }

        foreach ($newPropertyIds as $newId) {
            if (!$current->has($newId)) {
                $this->linkProperty($newId);
            }
        }
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
