<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\PreventionPlan;

use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translatable;
use App\Shared\Domain\Model\Locale\Translations;

final class PreventionPlanVersion implements Translatable
{
    /** @var iterable<int, ProposedAction> */
    private iterable $proposedActions = [];

    /** @var iterable<int, PreventionPlanSection> */
    private iterable $sections = [];

    /** @var iterable<PreventionPlanVersionTranslation>|Translations */
    private iterable $translations;

    private PreventionPlanVersionId $id;

    public function __construct(
        private PreventionPlan $preventionPlan,
        private int $versionNumber,
        private UserId $createdBy,
        private \DateTimeImmutable $createdAt,
        ?PreventionPlanVersionId $id = null,
        private PreventionPlanVersionStatus $status = PreventionPlanVersionStatus::InProgress,
        private ?EmployeeId $responsible = null,
    ) {
        $this->id = $id ?? PreventionPlanVersionId::generate();
        $this->translations = new Translations();
    }

    public function id(): PreventionPlanVersionId
    {
        return $this->id;
    }

    public function preventionPlan(): PreventionPlan
    {
        return $this->preventionPlan;
    }

    public function versionNumber(): int
    {
        return $this->versionNumber;
    }

    public function createdBy(): UserId
    {
        return $this->createdBy;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function responsible(): ?EmployeeId
    {
        return $this->responsible;
    }

    public function status(): PreventionPlanVersionStatus
    {
        return $this->status;
    }

    public function isInProgress(): bool
    {
        return PreventionPlanVersionStatus::InProgress === $this->status;
    }

    public function isValidated(): bool
    {
        return $this->status->isValidated();
    }

    public function isLoaded(): bool
    {
        return $this->status->isLoaded();
    }

    public function isGenerated(): bool
    {
        return $this->status->isGenerated();
    }

    public function isDraftApproved(): bool
    {
        return $this->status->isDraftApproved();
    }

    public function isClosed(): bool
    {
        return $this->status->isClosed();
    }

    public function markAsValidated(): void
    {
        $this->status = PreventionPlanVersionStatus::Validated;
    }

    public function markAsLoaded(): void
    {
        $this->status = PreventionPlanVersionStatus::Loaded;
    }

    public function markAsConfigured(): void
    {
        $this->status = PreventionPlanVersionStatus::Configured;
    }

    public function markAsGenerated(): void
    {
        $this->status = PreventionPlanVersionStatus::Generated;
    }

    public function markAsDraftApproved(): void
    {
        $this->status = PreventionPlanVersionStatus::DraftApproved;
    }

    public function markAsCompleted(): void
    {
        $this->status = PreventionPlanVersionStatus::Completed;
    }

    public function abandon(): void
    {
        $this->status = PreventionPlanVersionStatus::Abandoned;
    }

    public function pdfPath(): ?string
    {
        /** @var PreventionPlanVersionTranslation|null $translation */
        $translation = $this->translations()->currentTranslation();

        return null !== $translation ? $translation->pdfPath() : null;
    }

    public function translations(): Translations
    {
        if ($this->translations instanceof Translations) {
            return $this->translations;
        }

        $this->translations = new Translations(iterator_to_array($this->translations));

        return $this->translations;
    }

    public function savePDF(string $pdfPath, Locale $locale): void
    {
        $this->translations()->add(
            new PreventionPlanVersionTranslation(
                pdfPath: $pdfPath,
                locale: $locale,
                translatable: $this,
            ),
        );
    }

    public function proposedActions(): ProposedActions
    {
        return new ProposedActions($this->proposedActions);
    }

    public function sections(): PreventionPlanSections
    {
        return new PreventionPlanSections($this->sections);
    }
}
