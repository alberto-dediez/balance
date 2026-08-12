<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PublicLanding;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Library\Domain\Model\Media\MediaId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class PublicLandingBlockAdded extends DomainEvent
{
    protected static string $eventName = 'publicLandingBlockAdded';

    public function __construct(
        private PublicLandingId $publicLandingId,
        private PublicLandingBlockId $blockId,
        private int $position,
        private ?MediaId $image,
        private string $title,
        private string $text,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function publicLandingId(): PublicLandingId
    {
        return $this->publicLandingId;
    }

    public function blockId(): PublicLandingBlockId
    {
        return $this->blockId;
    }

    public function position(): int
    {
        return $this->position;
    }

    public function image(): ?MediaId
    {
        return $this->image;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function text(): string
    {
        return $this->text;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->publicLandingId();
    }

    public function payload(): array
    {
        return [
            'publicLandingId' => (string) $this->publicLandingId,
            'blockId' => (string) $this->blockId,
            'position' => $this->position,
            'image' => null !== $this->image ? (string) $this->image : null,
            'title' => $this->title,
            'text' => $this->text,
        ];
    }

    public static function rebuildFromPayload(DomainEventId $id, \DateTimeImmutable $occurredOn, array $payload = []): static
    {
        return new self(
            publicLandingId: new PublicLandingId($payload['publicLandingId']),
            blockId: new PublicLandingBlockId($payload['blockId']),
            position: (int) $payload['position'],
            image: null !== $payload['image'] ? new MediaId($payload['image']) : null,
            title: $payload['title'],
            text: $payload['text'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
