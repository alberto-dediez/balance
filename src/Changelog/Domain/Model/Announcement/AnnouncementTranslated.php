<?php

declare(strict_types=1);

namespace App\Changelog\Domain\Model\Announcement;

use App\Changelog\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Locale\Locale;

final class AnnouncementTranslated extends DomainEvent
{
    protected static string $eventName = 'announcementTranslated';

    public function __construct(
        private readonly AnnouncementId $announcementId,
        private readonly string $title,
        private readonly string $summary,
        private readonly string $body,
        private readonly ?YouTubeVideo $youtubeVideo,
        private readonly ?string $manualFileName,
        private readonly ?string $manualPath,
        private readonly Locale $locale,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function announcementId(): AnnouncementId
    {
        return $this->announcementId;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function summary(): string
    {
        return $this->summary;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function youtubeVideo(): ?YouTubeVideo
    {
        return $this->youtubeVideo;
    }

    public function manualFileName(): ?string
    {
        return $this->manualFileName;
    }

    public function manualPath(): ?string
    {
        return $this->manualPath;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->announcementId;
    }

    public function payload(): array
    {
        return [
            'announcementId' => (string) $this->announcementId,
            'locale' => $this->locale->value,
            'title' => $this->title,
            'summary' => $this->summary,
            'body' => $this->body,
            'youtubeVideoId' => $this->youtubeVideo?->id,
            'manualFileName' => $this->manualFileName,
            'manualPath' => $this->manualPath,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new AnnouncementTranslated(
            announcementId: new AnnouncementId($payload['announcementId']),
            title: $payload['title'],
            summary: $payload['summary'],
            body: $payload['body'],
            youtubeVideo: null !== $payload['youtubeVideoId'] ? YouTubeVideo::fromId($payload['youtubeVideoId']) : null,
            manualFileName: $payload['manualFileName'],
            manualPath: $payload['manualPath'],
            locale: Locale::from($payload['locale']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
