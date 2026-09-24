<?php

declare(strict_types=1);

namespace App\Changelog\Domain\Model\Announcement;

use App\Changelog\Domain\Model\ChangelogEntry\ChangelogCategory;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Domain\Model\Locale\Translatable;
use App\Shared\Domain\Model\Locale\Translations;

class Announcement implements Translatable, \Stringable
{
    use AggregateRoot {
        publish as protected publishEvent;
    }

    /** @var iterable<AnnouncementTranslation>|Translations */
    private iterable $translations;

    /**
     * @param DomainEvent[] $events
     */
    public function __construct(
        private AnnouncementId $id,
        private ChangelogCategory $category = ChangelogCategory::Feature,
        private \DateTimeImmutable $date = new \DateTimeImmutable('today'),
        private ?string $version = null,
        private string $title = '',
        private string $summary = '',
        private string $body = '',
        private ?YouTubeVideo $youtubeVideo = null,
        private ?string $manualFileName = null,
        private ?string $manualPath = null,
        private bool $published = false,
        private ?\DateTimeImmutable $publishedAt = null,
        private ?\DateTimeImmutable $scheduledAt = null,
        private bool $notifyOnPublish = false,
        private ?UserId $registeredBy = null,
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->translations = new Translations();
        $this->events = $events;
    }

    public function id(): AnnouncementId
    {
        return $this->id;
    }

    public function category(): ChangelogCategory
    {
        return $this->category;
    }

    public function date(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function version(): ?string
    {
        return $this->version;
    }

    public function title(?Locale $locale = null): string
    {
        return $this->translationIn($locale)?->title() ?? $this->title;
    }

    public function summary(?Locale $locale = null): string
    {
        return $this->translationIn($locale)?->summary() ?? $this->summary;
    }

    public function body(?Locale $locale = null): string
    {
        return $this->translationIn($locale)?->body() ?? $this->body;
    }

    public function youtubeVideo(?Locale $locale = null): ?YouTubeVideo
    {
        $translation = $this->translationIn($locale);

        return $translation instanceof AnnouncementTranslation ? $translation->youtubeVideo() : $this->youtubeVideo;
    }

    public function manualFileName(?Locale $locale = null): ?string
    {
        $translation = $this->translationIn($locale);

        return $translation instanceof AnnouncementTranslation ? $translation->manualFileName() : $this->manualFileName;
    }

    public function manualPath(?Locale $locale = null): ?string
    {
        $translation = $this->translationIn($locale);

        return $translation instanceof AnnouncementTranslation ? $translation->manualPath() : $this->manualPath;
    }

    public function hasVideo(?Locale $locale = null): bool
    {
        return $this->youtubeVideo($locale) instanceof YouTubeVideo;
    }

    public function hasManual(?Locale $locale = null): bool
    {
        return null !== $this->manualPath($locale);
    }

    public function isPublished(): bool
    {
        return $this->published;
    }

    public function isScheduled(): bool
    {
        return !$this->published && $this->scheduledAt instanceof \DateTimeImmutable;
    }

    public function status(): AnnouncementStatus
    {
        if ($this->published) {
            return AnnouncementStatus::Published;
        }

        if ($this->isScheduled()) {
            return AnnouncementStatus::Scheduled;
        }

        return AnnouncementStatus::Draft;
    }

    public function publishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function scheduledAt(): ?\DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function notifyOnPublish(): bool
    {
        return $this->notifyOnPublish;
    }

    public function registeredBy(): ?UserId
    {
        return $this->registeredBy;
    }

    public function translations(): Translations
    {
        if ($this->translations instanceof Translations) {
            return $this->translations;
        }

        $this->translations = new Translations(iterator_to_array($this->translations));

        return $this->translations;
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

    public function register(
        ChangelogCategory $category,
        \DateTimeImmutable $date,
        ?string $version,
        string $title,
        string $summary,
        string $body,
        ?YouTubeVideo $youtubeVideo,
        ?string $manualFileName,
        ?string $manualPath,
        ?UserId $registeredBy,
        ?Locale $locale = null,
    ): void {
        $this->recordApplyAndPublish(
            new AnnouncementRegistered(
                announcementId: $this->id(),
                category: $category,
                date: $date,
                version: $version,
                title: $title,
                summary: $summary,
                body: $body,
                youtubeVideo: $youtubeVideo,
                manualFileName: $manualFileName,
                manualPath: $manualPath,
                registeredBy: $registeredBy,
            ),
        );

        if ($locale instanceof Locale) {
            $this->translate($title, $summary, $body, $youtubeVideo, $manualFileName, $manualPath, $locale);
        }
    }

    public function update(
        ChangelogCategory $category,
        \DateTimeImmutable $date,
        ?string $version,
        string $title,
        string $summary,
        string $body,
        ?YouTubeVideo $youtubeVideo,
        ?string $manualFileName,
        ?string $manualPath,
        ?Locale $locale = null,
    ): void {
        $this->recordApplyAndPublish(
            new AnnouncementUpdated(
                announcementId: $this->id(),
                category: $category,
                date: $date,
                version: $version,
                title: $locale instanceof Locale ? $this->title : $title,
                summary: $locale instanceof Locale ? $this->summary : $summary,
                body: $locale instanceof Locale ? $this->body : $body,
                youtubeVideo: $locale instanceof Locale ? $this->youtubeVideo : $youtubeVideo,
                manualFileName: $locale instanceof Locale ? $this->manualFileName : $manualFileName,
                manualPath: $locale instanceof Locale ? $this->manualPath : $manualPath,
            ),
        );

        if ($locale instanceof Locale) {
            $this->translate($title, $summary, $body, $youtubeVideo, $manualFileName, $manualPath, $locale);
        }
    }

    public function translate(
        string $title,
        string $summary,
        string $body,
        ?YouTubeVideo $youtubeVideo,
        ?string $manualFileName,
        ?string $manualPath,
        Locale $locale,
    ): void {
        $this->recordApplyAndPublish(
            new AnnouncementTranslated(
                announcementId: $this->id(),
                title: $title,
                summary: $summary,
                body: $body,
                youtubeVideo: $youtubeVideo,
                manualFileName: $manualFileName,
                manualPath: $manualPath,
                locale: $locale,
            ),
        );
    }

    public function schedule(\DateTimeImmutable $scheduledAt, bool $notifyUsers): void
    {
        if ($this->published) {
            throw new \LogicException('A published announcement cannot be scheduled');
        }

        $this->recordApplyAndPublish(
            new AnnouncementPublicationScheduled(
                announcementId: $this->id(),
                scheduledAt: $scheduledAt,
                notifyUsers: $notifyUsers,
            ),
        );
    }

    public function publish(bool $notifyUsers): void
    {
        if ($this->published) {
            return;
        }

        $this->recordApplyAndPublish(
            new AnnouncementPublished(
                announcementId: $this->id(),
                publishedAt: new \DateTimeImmutable('NOW'),
                notifyUsers: $notifyUsers,
            ),
        );
    }

    public function unpublish(): void
    {
        if (!$this->published && !$this->scheduledAt instanceof \DateTimeImmutable) {
            return;
        }

        $this->recordApplyAndPublish(
            new AnnouncementUnpublished(
                announcementId: $this->id(),
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new AnnouncementRemoved(
                announcementId: $this->id(),
            ),
        );
    }

    public function applyAnnouncementRegistered(AnnouncementRegistered $event): void
    {
        $this->category = $event->category();
        $this->date = $event->date();
        $this->version = $event->version();
        $this->title = $event->title();
        $this->summary = $event->summary();
        $this->body = $event->body();
        $this->youtubeVideo = $event->youtubeVideo();
        $this->manualFileName = $event->manualFileName();
        $this->manualPath = $event->manualPath();
        $this->registeredBy = $event->registeredBy();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyAnnouncementUpdated(AnnouncementUpdated $event): void
    {
        $this->category = $event->category();
        $this->date = $event->date();
        $this->version = $event->version();
        $this->title = $event->title();
        $this->summary = $event->summary();
        $this->body = $event->body();
        $this->youtubeVideo = $event->youtubeVideo();
        $this->manualFileName = $event->manualFileName();
        $this->manualPath = $event->manualPath();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyAnnouncementTranslated(AnnouncementTranslated $event): void
    {
        $announcementTranslation = new AnnouncementTranslation(
            title: $event->title(),
            summary: $event->summary(),
            body: $event->body(),
            youtubeVideo: $event->youtubeVideo(),
            manualFileName: $event->manualFileName(),
            manualPath: $event->manualPath(),
            locale: $event->locale(),
            translatable: $this,
        );

        $this->translations()->add($announcementTranslation);
    }

    public function applyAnnouncementPublicationScheduled(AnnouncementPublicationScheduled $event): void
    {
        $this->scheduledAt = $event->scheduledAt();
        $this->notifyOnPublish = $event->notifyUsers();
    }

    public function applyAnnouncementPublished(AnnouncementPublished $event): void
    {
        $this->published = true;
        $this->publishedAt = $event->publishedAt();
        $this->scheduledAt = null;
        $this->notifyOnPublish = false;
    }

    public function applyAnnouncementUnpublished(AnnouncementUnpublished $event): void
    {
        $this->published = false;
        $this->scheduledAt = null;
        $this->notifyOnPublish = false;
    }

    public function applyAnnouncementRemoved(AnnouncementRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return $this->title();
    }

    protected function recordApplyAndPublish(DomainEvent $event): void
    {
        $this->record($event);
        $this->apply($event);
        $this->publishEvent($event);
    }

    private function translationIn(?Locale $locale): ?AnnouncementTranslation
    {
        $translation = $this->translations()->getTranslationFor($locale ?? LocaleGetter::locale());

        return $translation instanceof AnnouncementTranslation ? $translation : null;
    }
}
