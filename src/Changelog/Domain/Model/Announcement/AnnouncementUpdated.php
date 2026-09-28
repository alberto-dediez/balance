<?php

declare(strict_types=1);

namespace App\Changelog\Domain\Model\Announcement;

use App\Changelog\Domain\DomainEvent;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogCategory;
use App\Library\Domain\Model\Media\MediaId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class AnnouncementUpdated extends DomainEvent
{
    protected static string $eventName = 'announcementUpdated';

    public function __construct(
        private readonly AnnouncementId $announcementId,
        private readonly ChangelogCategory $category,
        private readonly \DateTimeImmutable $date,
        private readonly ?string $version,
        private readonly string $title,
        private readonly string $summary,
        private readonly string $body,
        private readonly ?YouTubeVideo $youtubeVideo,
        private readonly ?MediaId $manualId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function announcementId(): AnnouncementId
    {
        return $this->announcementId;
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

    public function manualId(): ?MediaId
    {
        return $this->manualId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->announcementId;
    }

    public function payload(): array
    {
        return [
            'announcementId' => (string) $this->announcementId,
            'category' => $this->category->value,
            'date' => $this->date->format('Y-m-d'),
            'version' => $this->version,
            'title' => $this->title,
            'summary' => $this->summary,
            'body' => $this->body,
            'youtubeVideoId' => $this->youtubeVideo?->id,
            'manualId' => $this->manualId instanceof MediaId ? (string) $this->manualId : null,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new AnnouncementUpdated(
            announcementId: new AnnouncementId($payload['announcementId']),
            category: ChangelogCategory::from($payload['category']),
            date: new \DateTimeImmutable($payload['date']),
            version: $payload['version'],
            title: $payload['title'],
            summary: $payload['summary'],
            body: $payload['body'],
            youtubeVideo: null !== $payload['youtubeVideoId'] ? YouTubeVideo::fromId($payload['youtubeVideoId']) : null,
            manualId: null !== ($payload['manualId'] ?? null) ? new MediaId($payload['manualId']) : null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
