<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\PreventionPlan;

use App\FoodLoss\Domain\Model\Action\ActionId;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Locale\Locale;

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
        private CompanyId $companyId = new CompanyId(''),
        private ?AccountId $accountId = null,
        private ?EmployeeId $responsible = null,
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

    public function status(): PreventionPlanVersionStatus
    {
        return $this->currentPlanVersion()->status();
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

    public function companyId(): CompanyId
    {
        return $this->companyId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function responsible(): ?EmployeeId
    {
        return $this->responsible;
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
        CompanyId $companyId,
        AccountId $accountId,
        UserId $registeredBy,
        ?EmployeeId $responsible = null,
    ): void {
        $this->recordApplyAndPublish(
            new PreventionPlanRegistered(
                preventionPlanId: $this->id(),
                name: $name,
                companyId: $companyId,
                accountId: $accountId,
                responsible: $responsible,
            ),
        );

        $this->recordApplyAndPublish(
            new PreventionPlanVersionCreated(
                preventionPlanId: $this->id(),
                versionNumber: 0,
                createdBy: $registeredBy,
                responsible: $responsible,
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
        CompanyId $companyId,
        UserId $updatedBy,
        ?EmployeeId $responsible = null,
    ): void {
        $propertiesChanged = $this->propertiesChanged($propertyIds);
        $responsibleChanged = $this->responsibleChanged($responsible);

        $this->recordApplyAndPublish(
            new PreventionPlanUpdated(
                preventionPlanId: $this->id(),
                name: $name,
                companyId: $companyId,
                responsible: $responsible,
            ),
        );

        if ($propertiesChanged || $responsibleChanged || $this->currentPlanVersion()->isClosed()) {
            $currentProposedActions = $this->currentPlanVersion()->proposedActions()->toArray();
            $newVersionNumber = $this->currentVersion + 1;

            $this->recordApplyAndPublish(
                new PreventionPlanVersionCreated(
                    preventionPlanId: $this->id(),
                    versionNumber: $newVersionNumber,
                    createdBy: $updatedBy,
                    responsible: $responsible,
                ),
            );

            foreach ($currentProposedActions as $proposedAction) {
                foreach ($propertyIds as $propertyId) {
                    if ($proposedAction->propertyId()->equals($propertyId)) {
                        $this->recordApplyAndPublish(
                            new ProposedActionAdded(
                                preventionPlanId: $this->id(),
                                versionNumber: $newVersionNumber,
                                propertyId: $proposedAction->propertyId(),
                                actionId: $proposedAction->actionId(),
                                criticalPointId: $proposedAction->criticalPointId(),
                            ),
                        );
                        break;
                    }
                }
            }
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

    public function currentPlanVersion(): PreventionPlanVersion
    {
        $version = $this->versions()->findByVersionNumber($this->currentVersion);

        assert(null !== $version);

        return $version;
    }

    public function addProposedAction(
        PropertyId $propertyId,
        ActionId $actionId,
        CriticalPointId $criticalPointId,
    ): void {
        $version = $this->currentPlanVersion();

        if ($version->proposedActions()->has($propertyId, $actionId, $criticalPointId)) {
            return;
        }

        $this->recordApplyAndPublish(
            new ProposedActionAdded(
                preventionPlanId: $this->id(),
                versionNumber: $this->currentVersion,
                propertyId: $propertyId,
                actionId: $actionId,
                criticalPointId: $criticalPointId,
            ),
        );
    }

    public function removeProposedActionsByCriticalPoint(CriticalPointId $criticalPointId): void
    {
        $version = $this->currentPlanVersion();

        foreach ($version->proposedActions()->toArray() as $proposedAction) {
            if ($proposedAction->criticalPointId()->equals($criticalPointId)) {
                $this->removeProposedAction($proposedAction->propertyId(), $proposedAction->actionId(), $criticalPointId);
            }
        }
    }

    public function removeProposedAction(
        PropertyId $propertyId,
        ActionId $actionId,
        CriticalPointId $criticalPointId,
    ): void {
        $version = $this->currentPlanVersion();

        if (!$version->proposedActions()->has($propertyId, $actionId, $criticalPointId)) {
            return;
        }

        $this->recordApplyAndPublish(
            new ProposedActionRemoved(
                preventionPlanId: $this->id(),
                versionNumber: $this->currentVersion,
                propertyId: $propertyId,
                actionId: $actionId,
                criticalPointId: $criticalPointId,
            ),
        );
    }

    public function applyPreventionPlanRegistered(PreventionPlanRegistered $event): void
    {
        $this->name = $event->name();
        $this->companyId = $event->companyId();
        $this->accountId = $event->accountId();
        $this->responsible = $event->responsible();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyPreventionPlanUpdated(PreventionPlanUpdated $event): void
    {
        $this->name = $event->name();
        $this->companyId = $event->companyId();
        $this->responsible = $event->responsible();
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
        if ($event->versionNumber() > 0) {
            $previousVersion = $this->versions()->findByVersionNumber($this->currentVersion);
            if (null !== $previousVersion && !$previousVersion->isClosed()) {
                $previousVersion->abandon();
            }
        }

        $version = new PreventionPlanVersion(
            preventionPlan: $this,
            versionNumber: $event->versionNumber(),
            createdBy: $event->createdBy(),
            createdAt: $event->occurredOn(),
            responsible: $event->responsible(),
        );

        $this->versions()->add($version);
        $this->currentVersion = $event->versionNumber();
    }

    public function markAsValidated(): void
    {
        $this->recordApplyAndPublish(
            new PreventionPlanVersionValidated(
                preventionPlanId: $this->id(),
                versionNumber: $this->currentVersion,
            ),
        );
    }

    public function applyPreventionPlanVersionValidated(PreventionPlanVersionValidated $event): void
    {
        $version = $this->versions()->findByVersionNumber($event->versionNumber());
        if (null === $version) {
            return;
        }

        $version->markAsValidated();
    }

    public function markAsLoaded(): void
    {
        $this->recordApplyAndPublish(
            new PreventionPlanVersionLoaded(
                preventionPlanId: $this->id(),
                versionNumber: $this->currentVersion,
            ),
        );
    }

    public function applyPreventionPlanVersionLoaded(PreventionPlanVersionLoaded $event): void
    {
        $version = $this->versions()->findByVersionNumber($event->versionNumber());
        if (null === $version) {
            return;
        }

        $version->markAsLoaded();
    }

    public function markAsConfigured(): void
    {
        $this->recordApplyAndPublish(
            new PreventionPlanVersionConfigured(
                preventionPlanId: $this->id(),
                versionNumber: $this->currentVersion,
            ),
        );
    }

    public function applyPreventionPlanVersionConfigured(PreventionPlanVersionConfigured $event): void
    {
        $version = $this->versions()->findByVersionNumber($event->versionNumber());
        if (null === $version) {
            return;
        }

        $version->markAsConfigured();
    }

    public function markAsGenerated(): void
    {
        $this->recordApplyAndPublish(
            new PreventionPlanVersionGenerated(
                preventionPlanId: $this->id(),
                versionNumber: $this->currentVersion,
            ),
        );
    }

    public function applyPreventionPlanVersionGenerated(PreventionPlanVersionGenerated $event): void
    {
        $version = $this->versions()->findByVersionNumber($event->versionNumber());
        if (null === $version) {
            return;
        }

        $version->markAsGenerated();
    }

    public function markAsDraftApproved(): void
    {
        $this->recordApplyAndPublish(
            new PreventionPlanVersionDraftApproved(
                preventionPlanId: $this->id(),
                versionNumber: $this->currentVersion,
            ),
        );
    }

    public function applyPreventionPlanVersionDraftApproved(PreventionPlanVersionDraftApproved $event): void
    {
        $version = $this->versions()->findByVersionNumber($event->versionNumber());
        if (null === $version) {
            return;
        }

        $version->markAsDraftApproved();
    }

    public function markAsCompleted(): void
    {
        $this->recordApplyAndPublish(
            new PreventionPlanVersionCompleted(
                preventionPlanId: $this->id(),
                versionNumber: $this->currentVersion,
            ),
        );
    }

    public function applyPreventionPlanVersionCompleted(PreventionPlanVersionCompleted $event): void
    {
        $version = $this->versions()->findByVersionNumber($event->versionNumber());
        if (null === $version) {
            return;
        }

        $version->markAsCompleted();
    }

    public function savePDF(string $pdfPath, Locale $locale): void
    {
        $this->recordApplyAndPublish(
            new PreventionPlanVersionPDFSaved(
                preventionPlanId: $this->id(),
                versionNumber: $this->currentVersion,
                pdfPath: $pdfPath,
                locale: $locale,
            ),
        );
    }

    public function applyPreventionPlanVersionPDFSaved(PreventionPlanVersionPDFSaved $event): void
    {
        $version = $this->versions()->findByVersionNumber($event->versionNumber());
        if (null === $version) {
            return;
        }

        $version->savePDF($event->pdfPath(), $event->locale());
    }

    public function getPDF(): ?string
    {
        $versions = $this->versions()->toArray();
        usort($versions, static fn(PreventionPlanVersion $a, PreventionPlanVersion $b) => $b->versionNumber() - $a->versionNumber());

        foreach ($versions as $version) {
            $pdfPath = $version->pdfPath();
            if (null !== $pdfPath) {
                return $pdfPath;
            }
        }

        return null;
    }

    public function clearSections(): void
    {
        $this->recordApplyAndPublish(
            new PreventionPlanVersionSectionsCleared(
                preventionPlanId: $this->id(),
                versionNumber: $this->currentVersion,
            ),
        );
    }

    public function addSection(string $title, string $content, int $order): void
    {
        $this->recordApplyAndPublish(
            new PreventionPlanSectionAdded(
                preventionPlanId: $this->id(),
                versionNumber: $this->currentVersion,
                sectionId: PreventionPlanSectionId::generate(),
                title: $title,
                content: $content,
                sectionOrder: $order,
            ),
        );
    }

    public function sections(): PreventionPlanSections
    {
        return $this->currentPlanVersion()->sections();
    }

    public function applyPreventionPlanVersionSectionsCleared(PreventionPlanVersionSectionsCleared $event): void
    {
        $version = $this->versions()->findByVersionNumber($event->versionNumber());
        if (null === $version) {
            return;
        }

        $version->sections()->clear();
    }

    public function applyPreventionPlanSectionAdded(PreventionPlanSectionAdded $event): void
    {
        $version = $this->versions()->findByVersionNumber($event->versionNumber());
        if (null === $version) {
            return;
        }

        $existing = $version->sections()->findByOrder($event->sectionOrder());
        if (null !== $existing) {
            $existing->update($event->title(), $event->content());

            return;
        }

        $version->sections()->add(
            new PreventionPlanSection(
                id: $event->sectionId(),
                preventionPlanVersion: $version,
                title: $event->title(),
                content: $event->content(),
                sectionOrder: $event->sectionOrder(),
            ),
        );
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
                criticalPointId: $event->criticalPointId(),
            ),
        );
    }

    public function applyProposedActionRemoved(ProposedActionRemoved $event): void
    {
        $version = $this->versions()->findByVersionNumber($event->versionNumber());
        if (null === $version) {
            return;
        }

        $version->proposedActions()->removeByIds($event->propertyId(), $event->actionId(), $event->criticalPointId());
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

    private function responsibleChanged(?EmployeeId $newResponsibleEmployeeId): bool
    {
        if (null === $this->responsible && null === $newResponsibleEmployeeId) {
            return false;
        }

        if (null === $this->responsible || null === $newResponsibleEmployeeId) {
            return true;
        }

        return !$this->responsible->equals($newResponsibleEmployeeId);
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
        return sprintf('%s #%s', $this->name(), $this->currentVersion());
    }
}
