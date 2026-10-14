<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\Storage;

use App\Library\Domain\Model\Media\MediaStorage;

final class MediaStorageSelector
{
    public function __construct(
        private readonly MediaStorage $primary,
        private readonly FallbackMediaStorage $fallback,
        private readonly bool $fallbackEnabled,
    ) {}

    public function select(): MediaStorage
    {
        return $this->fallbackEnabled ? $this->fallback : $this->primary;
    }
}
