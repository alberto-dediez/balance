<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\Storage;

use App\Library\Domain\Model\Media\MediaStorage;

final class MediaStorageRegistry
{
    /**
     * @param array<string, MediaStorage> $storages
     */
    public function __construct(
        private readonly array $storages,
        private readonly string $defaultDriver,
    ) {
        $this->get($defaultDriver);
    }

    public function get(string $driver): MediaStorage
    {
        if (!isset($this->storages[$driver])) {
            throw new \InvalidArgumentException(sprintf('Unknown media storage "%s", expected one of: %s', $driver, implode(', ', $this->drivers())));
        }

        return $this->storages[$driver];
    }

    public function default(): MediaStorage
    {
        return $this->get($this->defaultDriver);
    }

    /**
     * @return list<string>
     */
    public function drivers(): array
    {
        return array_keys($this->storages);
    }
}
