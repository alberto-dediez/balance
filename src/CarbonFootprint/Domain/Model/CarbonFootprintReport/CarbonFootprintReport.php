<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Domain\Model\Locale\Translatable;
use App\Shared\Domain\Model\Locale\Translations;
use Doctrine\Common\Collections\Collection;

class CarbonFootprintReport implements Translatable, \Stringable
{
    use AggregateRoot;

    /** @var iterable<CarbonFootprintReportTranslation>|Translations */
    private iterable $translations;

    /** @var iterable<CarbonFootprintReportSection> */
    private iterable $sections = [];

    /** @param array<\App\Shared\Domain\Event\DomainEvent> $events */
    public function __construct(
        private CarbonFootprintReportId $id,
        private CarbonFootprintReportSubjectType $subjectType = CarbonFootprintReportSubjectType::Event,
        private string $carbonFootprintId = '',
        private ?AccountId $accountId = null,
        private CarbonFootprintReportStatus $status = CarbonFootprintReportStatus::GeneratingTexts,
        private ?UserId $requestedBy = null,
        private ?Locale $baseLocale = null,
        private ?string $errorMessage = null,
        private ?\DateTimeImmutable $submittedAt = null,
        private ?\DateTimeImmutable $approvedAt = null,
        private bool $autoApproved = false,
        private ?UserId $reviewedBy = null,
        private ?\DateTimeImmutable $reviewedAt = null,
        private ?string $reviewComment = null,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $generatedAt = null,
    ) {
        $this->translations = new Translations();
        $this->events = $events;
    }

    public function id(): CarbonFootprintReportId
    {
        return $this->id;
    }

    public function subjectType(): CarbonFootprintReportSubjectType
    {
        return $this->subjectType;
    }

    public function carbonFootprintId(): string
    {
        return $this->carbonFootprintId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function status(): CarbonFootprintReportStatus
    {
        return $this->status;
    }

    public function requestedBy(): ?UserId
    {
        return $this->requestedBy;
    }

    public function baseLocale(): ?Locale
    {
        return $this->baseLocale;
    }

    public function errorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function submittedAt(): ?\DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function approvedAt(): ?\DateTimeImmutable
    {
        return $this->approvedAt;
    }

    public function isAutoApproved(): bool
    {
        return $this->autoApproved;
    }

    public function reviewedBy(): ?UserId
    {
        return $this->reviewedBy;
    }

    public function reviewedAt(): ?\DateTimeImmutable
    {
        return $this->reviewedAt;
    }

    public function reviewComment(): ?string
    {
        return $this->reviewComment;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function generatedAt(): ?\DateTimeImmutable
    {
        return $this->generatedAt;
    }

    public function isEditableByOwner(): bool
    {
        return $this->status->isDraft();
    }

    public function isReviewable(): bool
    {
        return $this->status->isPendingReview();
    }

    public function isDownloadable(): bool
    {
        return $this->status->isGenerated();
    }

    public function translations(): Translations
    {
        if ($this->translations instanceof Translations) {
            return $this->translations;
        }

        $this->translations = new Translations(iterator_to_array($this->translations));

        return $this->translations;
    }

    public function filePathFor(Locale $locale): ?string
    {
        /** @var CarbonFootprintReportTranslation|null $translation */
        $translation = $this->translations()->getTranslationFor($locale);

        return $translation?->filePath();
    }

    public function currentTranslation(): ?CarbonFootprintReportTranslation
    {
        /** @var CarbonFootprintReportTranslation|null $translation */
        $translation = $this->translations()->currentTranslation();

        if (null !== $translation) {
            return $translation;
        }

        /** @var CarbonFootprintReportTranslation|null $fallback */
        $fallback = $this->translations()->getTranslationFor(LocaleGetter::default());

        return $fallback;
    }

    /** @return list<CarbonFootprintReportSection> */
    public function sections(): array
    {
        $sections = [];

        foreach ($this->sections as $section) {
            $sections[] = $section;
        }

        usort($sections, static fn(CarbonFootprintReportSection $a, CarbonFootprintReportSection $b): int => $a->position() <=> $b->position());

        return $sections;
    }

    public function texts(): CarbonFootprintReportTexts
    {
        $texts = [];

        foreach ($this->sections() as $section) {
            $texts[$section->key()->value] = $section->content();
        }

        return new CarbonFootprintReportTexts($texts);
    }

    public function register(CarbonFootprintReportSubjectType $subjectType, string $carbonFootprintId, AccountId $accountId, UserId $requestedBy, Locale $baseLocale): void
    {
        $this->recordApplyAndPublish(
            new CarbonFootprintReportRegistered(
                carbonFootprintReportId: $this->id,
                subjectType: $subjectType,
                carbonFootprintId: $carbonFootprintId,
                accountId: $accountId,
                requestedBy: $requestedBy,
                baseLocale: $baseLocale,
            ),
        );
    }

    public function draftTexts(CarbonFootprintReportTexts $texts): void
    {
        $this->ensureStatusIn(CarbonFootprintReportStatus::GeneratingTexts);

        $this->recordApplyAndPublish(
            new CarbonFootprintReportTextsDrafted(
                carbonFootprintReportId: $this->id,
                sections: $texts->sections,
            ),
        );
    }

    public function changeTexts(CarbonFootprintReportTexts $texts): void
    {
        $this->ensureStatusIn(CarbonFootprintReportStatus::Draft, CarbonFootprintReportStatus::PendingReview);

        $this->recordApplyAndPublish(
            new CarbonFootprintReportTextsChanged(
                carbonFootprintReportId: $this->id,
                sections: $texts->sections,
            ),
        );
    }

    public function submitForReview(?CarbonFootprintReportTexts $texts = null): void
    {
        $this->ensureStatusIn(CarbonFootprintReportStatus::GeneratingTexts, CarbonFootprintReportStatus::Draft);
        $this->ensureTextsProvidedWhenGeneratingTexts($texts);
        $this->ensureTextsAreComplete($texts ?? $this->texts());

        $this->recordApplyAndPublish(
            new CarbonFootprintReportSubmittedForReview(
                carbonFootprintReportId: $this->id,
                sections: $texts?->sections,
            ),
        );
    }

    public function approve(UserId $reviewerId, bool $automatic, ?CarbonFootprintReportTexts $texts = null): void
    {
        $this->ensureStatusIn(CarbonFootprintReportStatus::GeneratingTexts, CarbonFootprintReportStatus::Draft, CarbonFootprintReportStatus::PendingReview);
        $this->ensureTextsProvidedWhenGeneratingTexts($texts);

        if (!$automatic && !$this->status->isPendingReview()) {
            throw new CarbonFootprintReportTransitionNotAllowedException('Only a report pending review can be approved by a reviewer');
        }

        if ($automatic && $this->status->isPendingReview()) {
            throw new CarbonFootprintReportTransitionNotAllowedException('A report pending review can only be approved by a reviewer');
        }

        $this->ensureTextsAreComplete($texts ?? $this->texts());

        $this->recordApplyAndPublish(
            new CarbonFootprintReportApproved(
                carbonFootprintReportId: $this->id,
                reviewerId: $reviewerId,
                automatic: $automatic,
                sections: $texts?->sections,
            ),
        );
    }

    public function reject(UserId $reviewerId, string $comment): void
    {
        $this->ensureStatusIn(CarbonFootprintReportStatus::PendingReview);

        if ('' === trim($comment)) {
            throw new CarbonFootprintReportRejectionCommentRequiredException('A rejection requires a comment');
        }

        $this->recordApplyAndPublish(
            new CarbonFootprintReportRejected(
                carbonFootprintReportId: $this->id,
                reviewerId: $reviewerId,
                comment: $comment,
            ),
        );
    }

    public function translate(string $filePath, Locale $locale): void
    {
        $this->ensureStatusIn(CarbonFootprintReportStatus::GeneratingPdf);

        $this->recordApplyAndPublish(
            new CarbonFootprintReportTranslated(
                carbonFootprintReportId: $this->id,
                filePath: $filePath,
                locale: $locale,
            ),
        );
    }

    public function generate(): void
    {
        $this->ensureStatusIn(CarbonFootprintReportStatus::GeneratingPdf);

        $this->recordApplyAndPublish(
            new CarbonFootprintReportGenerated(
                carbonFootprintReportId: $this->id,
            ),
        );
    }

    public function fail(string $errorMessage): void
    {
        $this->ensureStatusIn(CarbonFootprintReportStatus::GeneratingTexts, CarbonFootprintReportStatus::GeneratingPdf);

        $this->recordApplyAndPublish(
            new CarbonFootprintReportFailed(
                carbonFootprintReportId: $this->id,
                errorMessage: $errorMessage,
            ),
        );
    }

    public function reissue(): void
    {
        $this->ensureStatusIn(CarbonFootprintReportStatus::Generated);

        $this->recordApplyAndPublish(
            new CarbonFootprintReportReissued(
                carbonFootprintReportId: $this->id,
            ),
        );
    }

    public function retry(): void
    {
        $this->ensureStatusIn(CarbonFootprintReportStatus::Failed);

        $this->recordApplyAndPublish(
            new CarbonFootprintReportRetried(
                carbonFootprintReportId: $this->id,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new CarbonFootprintReportRemoved(
                carbonFootprintReportId: $this->id,
            ),
        );
    }

    public function applyCarbonFootprintReportRegistered(CarbonFootprintReportRegistered $event): void
    {
        $this->subjectType = $event->subjectType();
        $this->carbonFootprintId = $event->carbonFootprintId();
        $this->accountId = $event->accountId();
        $this->requestedBy = $event->requestedBy();
        $this->baseLocale = $event->baseLocale();
        $this->status = CarbonFootprintReportStatus::GeneratingTexts;
        $this->registeredAt = $event->occurredOn();
    }

    public function applyCarbonFootprintReportTextsDrafted(CarbonFootprintReportTextsDrafted $event): void
    {
        $this->replaceSections($event->sections());
        $this->status = CarbonFootprintReportStatus::Draft;
    }

    public function applyCarbonFootprintReportTextsChanged(CarbonFootprintReportTextsChanged $event): void
    {
        $this->replaceSections($event->sections());
    }

    public function applyCarbonFootprintReportSubmittedForReview(CarbonFootprintReportSubmittedForReview $event): void
    {
        if (null !== $event->sections()) {
            $this->replaceSections($event->sections());
        }

        $this->status = CarbonFootprintReportStatus::PendingReview;
        $this->submittedAt = $event->occurredOn();
    }

    public function applyCarbonFootprintReportApproved(CarbonFootprintReportApproved $event): void
    {
        if (null !== $event->sections()) {
            $this->replaceSections($event->sections());
        }

        $this->status = CarbonFootprintReportStatus::GeneratingPdf;
        $this->approvedAt = $event->occurredOn();
        $this->autoApproved = $event->automatic();
        $this->reviewedBy = $event->reviewerId();
        $this->reviewedAt = $event->occurredOn();
        $this->errorMessage = null;
    }

    public function applyCarbonFootprintReportRejected(CarbonFootprintReportRejected $event): void
    {
        $this->status = CarbonFootprintReportStatus::Rejected;
        $this->reviewedBy = $event->reviewerId();
        $this->reviewedAt = $event->occurredOn();
        $this->reviewComment = $event->comment();
    }

    public function applyCarbonFootprintReportTranslated(CarbonFootprintReportTranslated $event): void
    {
        $translation = new CarbonFootprintReportTranslation(
            filePath: $event->filePath(),
            locale: $event->locale(),
            translatable: $this,
        );

        $this->translations()->add($translation);
    }

    public function applyCarbonFootprintReportGenerated(CarbonFootprintReportGenerated $event): void
    {
        $this->status = CarbonFootprintReportStatus::Generated;
        $this->errorMessage = null;
        $this->generatedAt = $event->occurredOn();
    }

    public function applyCarbonFootprintReportReissued(CarbonFootprintReportReissued $event): void
    {
        $this->replaceSections([]);
        $this->status = CarbonFootprintReportStatus::GeneratingTexts;
        $this->errorMessage = null;
        $this->submittedAt = null;
        $this->approvedAt = null;
        $this->autoApproved = false;
        $this->reviewedBy = null;
        $this->reviewedAt = null;
        $this->reviewComment = null;
    }

    public function applyCarbonFootprintReportRetried(CarbonFootprintReportRetried $event): void
    {
        if (null === $this->approvedAt) {
            $this->replaceSections([]);
            $this->status = CarbonFootprintReportStatus::GeneratingTexts;
        } else {
            $this->status = CarbonFootprintReportStatus::GeneratingPdf;
        }

        $this->errorMessage = null;
    }

    public function applyCarbonFootprintReportFailed(CarbonFootprintReportFailed $event): void
    {
        $this->status = CarbonFootprintReportStatus::Failed;
        $this->errorMessage = $event->errorMessage();
    }

    public function applyCarbonFootprintReportRemoved(CarbonFootprintReportRemoved $event): void {}

    /** @param array<string, string> $sections */
    private function replaceSections(array $sections): void
    {
        foreach ($this->sections as $key => $section) {
            if (array_key_exists($section->key()->value, $sections)) {
                continue;
            }

            if (is_array($this->sections)) {
                unset($this->sections[$key]);
            } elseif ($this->sections instanceof Collection) {
                $this->sections->removeElement($section);
            }
        }

        $position = 0;

        foreach ($sections as $keyValue => $content) {
            $key = CarbonFootprintReportSectionKey::from($keyValue);
            $existing = $this->sectionFor($key);

            if (null !== $existing) {
                $existing->changeContent($content, $position);
            } else {
                $section = new CarbonFootprintReportSection($this, $key, $content, $position);

                if (is_array($this->sections)) {
                    $this->sections[] = $section;
                } elseif ($this->sections instanceof Collection) {
                    $this->sections->add($section);
                }
            }

            ++$position;
        }
    }

    private function sectionFor(CarbonFootprintReportSectionKey $key): ?CarbonFootprintReportSection
    {
        foreach ($this->sections as $section) {
            if ($section->key() === $key) {
                return $section;
            }
        }

        return null;
    }

    private function ensureStatusIn(CarbonFootprintReportStatus ...$allowed): void
    {
        if (!in_array($this->status, $allowed, true)) {
            throw new CarbonFootprintReportTransitionNotAllowedException(sprintf('Transition not allowed from status "%s"', $this->status->value));
        }
    }

    private function ensureTextsProvidedWhenGeneratingTexts(?CarbonFootprintReportTexts $texts): void
    {
        if ($this->status->isGeneratingTexts() && null === $texts) {
            throw new CarbonFootprintReportTransitionNotAllowedException('Texts are required while the report is generating them');
        }
    }

    private function ensureTextsAreComplete(CarbonFootprintReportTexts $texts): void
    {
        if (!$texts->isCompleteFor($this->subjectType)) {
            throw new CarbonFootprintReportTextsIncompleteException('Every required section of the report must have content');
        }
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
