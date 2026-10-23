<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\Storage;

use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaPath;
use App\Library\Domain\Model\Media\MediaStorage;
use App\Library\Domain\Model\Media\NotStoredMediaException;
use App\Library\Domain\Model\StorageSync\MediaStorageFallbackActivated;
use App\Library\Domain\Model\StorageSync\StorageSyncTask;
use App\Library\Domain\Model\StorageSync\StorageSyncTaskRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;

final class FallbackMediaStorage implements MediaStorage
{
    public function __construct(
        private readonly MediaStorage $primary,
        private readonly MediaStorage $fallback,
        private readonly StorageSyncTaskRepository $tasks,
    ) {}

    public function store(MediaId $mediaId, ?AccountId $accountId, string $extension, string $localPath): string
    {
        $path = MediaPath::from($mediaId, $accountId, $extension);

        $this->write($path, $localPath);

        return $path;
    }

    public function write(string $path, string $localPath): void
    {
        if ($this->primary === $this->fallback) {
            $this->primary->write($path, $localPath);

            return;
        }

        try {
            $this->primary->write($path, $localPath);
            $this->forget($path);

            return;
        } catch (NotStoredMediaException $primaryException) {
        }

        try {
            $this->fallback->write($path, $localPath);
        } catch (NotStoredMediaException) {
            throw $primaryException;
        }

        $this->enqueue($path, $primaryException->getMessage());
    }

    public function read(string $path): mixed
    {
        try {
            return $this->primary->read($path);
        } catch (NotStoredMediaException $primaryException) {
            try {
                return $this->fallback->read($path);
            } catch (NotStoredMediaException) {
                throw $primaryException;
            }
        }
    }

    public function delete(string $path): void
    {
        if ('' === $path) {
            return;
        }

        if ($this->primary === $this->fallback) {
            $this->primary->delete($path);

            return;
        }

        $task = $this->tasks->find($path);

        if ($task instanceof StorageSyncTask) {
            $this->fallback->delete($path);
            $this->tasks->remove($task);

            return;
        }

        $this->primary->delete($path);
        $this->fallback->delete($path);
    }

    public function exists(string $path): bool
    {
        try {
            if ($this->primary->exists($path)) {
                return true;
            }
        } catch (NotStoredMediaException) {
        }

        return $this->fallback->exists($path);
    }

    private function enqueue(string $path, string $error): void
    {
        $wasIdle = 0 === $this->tasks->countPending();

        $this->tasks->save(new StorageSyncTask($path));

        if ($wasIdle) {
            DomainEventPublisher::instance()->publish(new MediaStorageFallbackActivated($path, $error));
        }
    }

    private function forget(string $path): void
    {
        $task = $this->tasks->find($path);

        if ($task instanceof StorageSyncTask) {
            $this->tasks->remove($task);
        }
    }
}
