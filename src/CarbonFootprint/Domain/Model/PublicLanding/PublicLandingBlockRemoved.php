<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PublicLanding;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class PublicLandingBlockRemoved extends DomainEvent
{
    protected static string $eventName = 'publicLandingBlockRemoved';

    public function __construct(
        private PublicLandingId $publicLandingId,
        private PublicLandingBlockId $blockId,
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

    public function aggregateRootId(): AggregateRootId
    {
        return $this->publicLandingId();
    }

    public function payload(): array
    {
        return [
            'publicLandingId' => (string) $this->publicLandingId,
            'blockId' => (string) $this->blockId,
        ];
    }

    public static function rebuildFromPayload(DomainEventId $id, \DateTimeImmutable $occurredOn, array $payload = []): static
    {
        return new self(
            publicLandingId: new PublicLandingId($payload['publicLandingId']),
            blockId: new PublicLandingBlockId($payload['blockId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
