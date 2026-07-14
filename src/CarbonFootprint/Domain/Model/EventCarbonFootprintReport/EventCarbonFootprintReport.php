<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Domain\Model\Locale\Translatable;
use App\Shared\Domain\Model\Locale\Translations;

class EventCarbonFootprintReport implements Translatable, \Stringable
{
    use AggregateRoot;

    /** @var iterable<EventCarbonFootprintReportTranslation>|Translations */
    private iterable $translations;

    /** @param array<\App\Shared\Domain\Event\DomainEvent> $events */
    public function __construct(
        private EventCarbonFootprintReportId $id,
        private ?EventCarbonFootprintId $eventCarbonFootprintId = null,
        private ?AccountId $accountId = null,
        private EventCarbonFootprintReportStatus $status = EventCarbonFootprintReportStatus::Pending,
        private ?UserId $requestedBy = null,
        private ?string $errorMessage = null,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $generatedAt = null,
    ) {
        $this->translations = new Translations();
        $this->events = $events;
    }

    public function id(): EventCarbonFootprintReportId
    {
        return $this->id;
    }

    public function eventCarbonFootprintId(): ?EventCarbonFootprintId
    {
        return $this->eventCarbonFootprintId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function status(): EventCarbonFootprintReportStatus
    {
        return $this->status;
    }

    public function requestedBy(): ?UserId
    {
        return $this->requestedBy;
    }

    public function errorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function generatedAt(): ?\DateTimeImmutable
    {
        return $this->generatedAt;
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
        /** @var EventCarbonFootprintReportTranslation|null $translation */
        $translation = $this->translations()->getTranslationFor($locale);

        return $translation?->filePath();
    }

    public function currentTranslation(): ?EventCarbonFootprintReportTranslation
    {
        /** @var EventCarbonFootprintReportTranslation|null $translation */
        $translation = $this->translations()->currentTranslation();

        if (null !== $translation) {
            return $translation;
        }

        /** @var EventCarbonFootprintReportTranslation|null $fallback */
        $fallback = $this->translations()->getTranslationFor(LocaleGetter::default());

        return $fallback;
    }

    public function register(EventCarbonFootprintId $eventCarbonFootprintId, AccountId $accountId, UserId $requestedBy): void
    {
        $this->recordApplyAndPublish(
            new EventCarbonFootprintReportRegistered(
                eventCarbonFootprintReportId: $this->id,
                eventCarbonFootprintId: $eventCarbonFootprintId,
                accountId: $accountId,
                requestedBy: $requestedBy,
            ),
        );
    }

    public function translate(string $filePath, Locale $locale): void
    {
        $this->recordApplyAndPublish(
            new EventCarbonFootprintReportTranslated(
                eventCarbonFootprintReportId: $this->id,
                filePath: $filePath,
                locale: $locale,
            ),
        );
    }

    public function generate(): void
    {
        $this->recordApplyAndPublish(
            new EventCarbonFootprintReportGenerated(
                eventCarbonFootprintReportId: $this->id,
            ),
        );
    }

    public function fail(string $errorMessage): void
    {
        $this->recordApplyAndPublish(
            new EventCarbonFootprintReportFailed(
                eventCarbonFootprintReportId: $this->id,
                errorMessage: $errorMessage,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new EventCarbonFootprintReportRemoved(
                eventCarbonFootprintReportId: $this->id,
            ),
        );
    }

    public function applyEventCarbonFootprintReportRegistered(EventCarbonFootprintReportRegistered $event): void
    {
        $this->eventCarbonFootprintId = $event->eventCarbonFootprintId();
        $this->accountId = $event->accountId();
        $this->requestedBy = $event->requestedBy();
        $this->status = EventCarbonFootprintReportStatus::Pending;
        $this->registeredAt = $event->occurredOn();
    }

    public function applyEventCarbonFootprintReportTranslated(EventCarbonFootprintReportTranslated $event): void
    {
        $translation = new EventCarbonFootprintReportTranslation(
            filePath: $event->filePath(),
            locale: $event->locale(),
            translatable: $this,
        );

        $this->translations()->add($translation);
    }

    public function applyEventCarbonFootprintReportGenerated(EventCarbonFootprintReportGenerated $event): void
    {
        $this->status = EventCarbonFootprintReportStatus::Generated;
        $this->errorMessage = null;
        $this->generatedAt = $event->occurredOn();
    }

    public function applyEventCarbonFootprintReportFailed(EventCarbonFootprintReportFailed $event): void
    {
        $this->status = EventCarbonFootprintReportStatus::Failed;
        $this->errorMessage = $event->errorMessage();
    }

    public function applyEventCarbonFootprintReportRemoved(EventCarbonFootprintReportRemoved $event): void {}

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
