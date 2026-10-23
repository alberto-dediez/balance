<?php

declare(strict_types=1);

namespace App\Library\Application\Media\SyncStorage;

use App\Library\Application\Media\MediaStorageFileCopier;
use App\Library\Domain\Model\Media\MediaStorage;
use App\Library\Domain\Model\StorageSync\MediaStorageSynchronized;
use App\Library\Domain\Model\StorageSync\StorageSyncTaskRepository;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Event\DomainEventPublisher;

class SyncMediaStorageService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly StorageSyncTaskRepository $tasks,
        private readonly MediaStorage $primary,
        private readonly MediaStorage $fallback,
        private readonly MediaStorageFileCopier $copier,
    ) {}

    public function __invoke(SyncMediaStorageRequest $request, User $user): SyncMediaStorageResponse
    {
        if ($this->primary === $this->fallback || 0 === $this->tasks->countPending()) {
            return new SyncMediaStorageResponse(
                uploaded: 0,
                dropped: 0,
                failed: 0,
                pending: $this->tasks->countPending(),
            );
        }

        $uploaded = 0;
        $dropped = 0;
        $failed = 0;
        $lastError = null;

        foreach ($this->tasks->findPending($request->batchSize) as $task) {
            $path = $task->path();

            try {
                if (!$this->fallback->exists($path)) {
                    $this->tasks->remove($task);
                    ++$dropped;

                    continue;
                }

                $this->copier->copy($path, $this->fallback, $this->primary);
                $this->fallback->delete($path);
                $this->tasks->remove($task);
                ++$uploaded;
            } catch (\Throwable $exception) {
                $task->recordFailure($exception->getMessage(), $request->now);
                $this->tasks->save($task);
                $failed = 1;
                $lastError = $exception->getMessage();

                break;
            }
        }

        $pending = $this->tasks->countPending();

        if ($uploaded > 0 && 0 === $pending) {
            DomainEventPublisher::instance()->publish(new MediaStorageSynchronized($uploaded));
        }

        return new SyncMediaStorageResponse(
            uploaded: $uploaded,
            dropped: $dropped,
            failed: $failed,
            pending: $pending,
            lastError: $lastError,
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof SyncMediaStorageRequest;
    }
}
