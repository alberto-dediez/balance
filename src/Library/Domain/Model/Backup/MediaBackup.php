<?php

declare(strict_types=1);

namespace App\Library\Domain\Model\Backup;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
class MediaBackup
{
    public function __construct(
        private readonly string $path,
        private readonly string $mediaId,
        private readonly \DateTimeImmutable $backedUpAt,
        private ?\DateTimeImmutable $removedAt = null,
    ) {}

    public function path(): string
    {
        return $this->path;
    }

    public function mediaId(): string
    {
        return $this->mediaId;
    }

    public function backedUpAt(): \DateTimeImmutable
    {
        return $this->backedUpAt;
    }

    public function removedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }

    public function isRemoved(): bool
    {
        return null !== $this->removedAt;
    }

    public function markRemoved(\DateTimeImmutable $at): void
    {
        if (null !== $this->removedAt) {
            return;
        }

        $this->removedAt = $at;
    }
}
