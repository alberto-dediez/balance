<?php

declare(strict_types=1);

namespace App\Library\Domain\Model\Media;

use App\Library\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MediaDetailsChanged extends DomainEvent
{
    protected static string $eventName = 'mediaDetailsChanged';

    public function __construct(
        private readonly MediaId $mediaId,
        private readonly string $name,
        private readonly ?string $description = null,
        private readonly ?string $alternativeText = null,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function mediaId(): MediaId
    {
        return $this->mediaId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function alternativeText(): ?string
    {
        return $this->alternativeText;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->mediaId;
    }

    public function payload(): array
    {
        return [
            'mediaId' => (string) $this->mediaId,
            'name' => $this->name,
            'description' => $this->description,
            'alternativeText' => $this->alternativeText,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new MediaDetailsChanged(
            mediaId: new MediaId($payload['mediaId']),
            name: $payload['name'],
            description: $payload['description'],
            alternativeText: $payload['alternativeText'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
