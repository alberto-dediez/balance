<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PublicLanding;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Library\Domain\Model\Media\MediaId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class PublicLandingUpdated extends DomainEvent
{
    protected static string $eventName = 'publicLandingUpdated';

    public function __construct(
        private PublicLandingId $publicLandingId,
        private bool $active,
        private string $title,
        private string $subtitle,
        private string $intro,
        private ?MediaId $heroImage,
        private string $ctaLabel,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function publicLandingId(): PublicLandingId
    {
        return $this->publicLandingId;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function subtitle(): string
    {
        return $this->subtitle;
    }

    public function intro(): string
    {
        return $this->intro;
    }

    public function heroImage(): ?MediaId
    {
        return $this->heroImage;
    }

    public function ctaLabel(): string
    {
        return $this->ctaLabel;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->publicLandingId();
    }

    public function payload(): array
    {
        return [
            'publicLandingId' => (string) $this->publicLandingId,
            'active' => $this->active,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'intro' => $this->intro,
            'heroImage' => null !== $this->heroImage ? (string) $this->heroImage : null,
            'ctaLabel' => $this->ctaLabel,
        ];
    }

    public static function rebuildFromPayload(DomainEventId $id, \DateTimeImmutable $occurredOn, array $payload = []): static
    {
        return new self(
            publicLandingId: new PublicLandingId($payload['publicLandingId']),
            active: (bool) $payload['active'],
            title: $payload['title'],
            subtitle: $payload['subtitle'],
            intro: $payload['intro'],
            heroImage: null !== $payload['heroImage'] ? new MediaId($payload['heroImage']) : null,
            ctaLabel: $payload['ctaLabel'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
