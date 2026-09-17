<?php

declare(strict_types=1);

namespace App\Bioscore\Domain\Model\BioscoreCatalogItem;

enum BioscoreMappingStatus: string
{
    case Unmapped = 'unmapped';
    case AutoMapped = 'auto_mapped';
    case ManuallyMapped = 'manually_mapped';
    case Ignored = 'ignored';

    public function isMapped(): bool
    {
        return self::AutoMapped === $this || self::ManuallyMapped === $this;
    }

    public function style(): string
    {
        return match ($this) {
            self::Unmapped => 'bg-warning text-dark',
            self::AutoMapped => 'bg-info',
            self::ManuallyMapped => 'bg-success',
            self::Ignored => 'bg-secondary',
        };
    }
}
