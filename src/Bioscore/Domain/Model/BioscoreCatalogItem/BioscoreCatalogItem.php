<?php

declare(strict_types=1);

namespace App\Bioscore\Domain\Model\BioscoreCatalogItem;

use App\Shared\Domain\Model\AggregateRoot;

class BioscoreCatalogItem implements \Stringable
{
    use AggregateRoot;

    /** @param array<mixed> $events */
    public function __construct(
        private BioscoreCatalogItemId $id,
        private BioscoreCatalog $catalog = BioscoreCatalog::FacilityFuel,
        private int $bioscoreId = 0,
        private string $name = '',
        private string $unit = '',
        private ?string $targetId = null,
        private BioscoreMappingStatus $status = BioscoreMappingStatus::Unmapped,
        private bool $withdrawn = false,
        array $events = [],
        private \DateTimeImmutable $firstSeenAt = new \DateTimeImmutable('NOW'),
        private \DateTimeImmutable $lastSeenAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $mappedAt = null,
        private ?\DateTimeImmutable $withdrawnAt = null,
        private ?\DateTimeImmutable $updatedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): BioscoreCatalogItemId
    {
        return $this->id;
    }

    public function catalog(): BioscoreCatalog
    {
        return $this->catalog;
    }

    public function bioscoreId(): int
    {
        return $this->bioscoreId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function unit(): string
    {
        return $this->unit;
    }

    public function targetId(): ?string
    {
        return $this->targetId;
    }

    public function status(): BioscoreMappingStatus
    {
        return $this->status;
    }

    public function isMapped(): bool
    {
        return $this->status->isMapped();
    }

    public function isIgnored(): bool
    {
        return BioscoreMappingStatus::Ignored === $this->status;
    }

    public function isWithdrawn(): bool
    {
        return $this->withdrawn;
    }

    public function isPending(): bool
    {
        return BioscoreMappingStatus::Unmapped === $this->status && !$this->withdrawn;
    }

    public function firstSeenAt(): \DateTimeImmutable
    {
        return $this->firstSeenAt;
    }

    public function lastSeenAt(): \DateTimeImmutable
    {
        return $this->lastSeenAt;
    }

    public function mappedAt(): ?\DateTimeImmutable
    {
        return $this->mappedAt;
    }

    public function withdrawnAt(): ?\DateTimeImmutable
    {
        return $this->withdrawnAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function register(BioscoreCatalog $catalog, int $bioscoreId, string $name, string $unit, \DateTimeImmutable $seenAt): void
    {
        $this->recordApplyAndPublish(
            new BioscoreCatalogItemRegistered(
                itemId: $this->id(),
                catalog: $catalog,
                bioscoreId: $bioscoreId,
                name: $name,
                unit: $unit,
                seenAt: $seenAt,
            ),
        );
    }

    public function refresh(string $name, string $unit, \DateTimeImmutable $seenAt): void
    {
        if ($this->withdrawn) {
            $this->restore($seenAt);
        }

        if ($name === $this->name && $unit === $this->unit) {
            $this->lastSeenAt = $seenAt;

            return;
        }

        $this->recordApplyAndPublish(
            new BioscoreCatalogItemUpdated(
                itemId: $this->id(),
                name: $name,
                unit: $unit,
                seenAt: $seenAt,
            ),
        );
    }

    public function map(string $targetId, bool $automatic): void
    {
        $status = $automatic ? BioscoreMappingStatus::AutoMapped : BioscoreMappingStatus::ManuallyMapped;

        if ($targetId === $this->targetId && $status === $this->status) {
            return;
        }

        $this->recordApplyAndPublish(
            new BioscoreCatalogItemMapped(
                itemId: $this->id(),
                targetId: $targetId,
                automatic: $automatic,
            ),
        );
    }

    public function unmap(): void
    {
        if (null === $this->targetId && BioscoreMappingStatus::Unmapped === $this->status) {
            return;
        }

        $this->recordApplyAndPublish(
            new BioscoreCatalogItemUnmapped(
                itemId: $this->id(),
                previousTargetId: $this->targetId,
            ),
        );
    }

    public function ignore(): void
    {
        if (BioscoreMappingStatus::Ignored === $this->status) {
            return;
        }

        $this->recordApplyAndPublish(
            new BioscoreCatalogItemIgnored(
                itemId: $this->id(),
                previousTargetId: $this->targetId,
            ),
        );
    }

    public function withdraw(\DateTimeImmutable $at): void
    {
        if ($this->withdrawn) {
            return;
        }

        $this->recordApplyAndPublish(
            new BioscoreCatalogItemWithdrawn(
                itemId: $this->id(),
                at: $at,
            ),
        );
    }

    public function restore(\DateTimeImmutable $at): void
    {
        if (!$this->withdrawn) {
            return;
        }

        $this->recordApplyAndPublish(
            new BioscoreCatalogItemRestored(
                itemId: $this->id(),
                at: $at,
            ),
        );
    }

    public function applyBioscoreCatalogItemRegistered(BioscoreCatalogItemRegistered $event): void
    {
        $this->catalog = $event->catalog();
        $this->bioscoreId = $event->bioscoreId();
        $this->name = $event->name();
        $this->unit = $event->unit();
        $this->firstSeenAt = $event->seenAt();
        $this->lastSeenAt = $event->seenAt();
    }

    public function applyBioscoreCatalogItemUpdated(BioscoreCatalogItemUpdated $event): void
    {
        $this->name = $event->name();
        $this->unit = $event->unit();
        $this->lastSeenAt = $event->seenAt();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyBioscoreCatalogItemMapped(BioscoreCatalogItemMapped $event): void
    {
        $this->targetId = $event->targetId();
        $this->status = $event->isAutomatic() ? BioscoreMappingStatus::AutoMapped : BioscoreMappingStatus::ManuallyMapped;
        $this->mappedAt = $event->occurredOn();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyBioscoreCatalogItemUnmapped(BioscoreCatalogItemUnmapped $event): void
    {
        $this->targetId = null;
        $this->status = BioscoreMappingStatus::Unmapped;
        $this->mappedAt = null;
        $this->updatedAt = $event->occurredOn();
    }

    public function applyBioscoreCatalogItemIgnored(BioscoreCatalogItemIgnored $event): void
    {
        $this->targetId = null;
        $this->status = BioscoreMappingStatus::Ignored;
        $this->mappedAt = null;
        $this->updatedAt = $event->occurredOn();
    }

    public function applyBioscoreCatalogItemWithdrawn(BioscoreCatalogItemWithdrawn $event): void
    {
        $this->withdrawn = true;
        $this->withdrawnAt = $event->at();
    }

    public function applyBioscoreCatalogItemRestored(BioscoreCatalogItemRestored $event): void
    {
        $this->withdrawn = false;
        $this->withdrawnAt = null;
        $this->lastSeenAt = $event->at();
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
