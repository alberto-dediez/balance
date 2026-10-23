<?php

declare(strict_types=1);

namespace App\Library\Application\Media\MigrateStorage;

use App\Library\Application\Media\MediaStorageFileCopier;
use App\Library\Domain\Model\Media\MediaFilter;
use App\Library\Domain\Model\Media\MediaRepository;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class MigrateMediaStorageService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly MediaRepository $mediaRepository,
        private readonly MediaStorageFileCopier $copier,
    ) {}

    public function __invoke(MigrateMediaStorageRequest $request, User $user): MigrateMediaStorageResponse
    {
        $migrated = 0;
        $alreadyInTarget = 0;
        $missingInSource = 0;
        $deletedFromSource = 0;
        $failures = [];

        $filter = new MediaFilter(limit: max(1, $request->batchSize));
        $page = 0;

        while (true) {
            $filter->page = $page;

            $batch = $this->mediaRepository->findByFilter($filter);

            if ([] === $batch) {
                break;
            }

            foreach ($batch as $media) {
                $path = $media->path();

                try {
                    if ($request->target->exists($path)) {
                        ++$alreadyInTarget;

                        if ($request->deleteSource && !$request->dryRun && $request->source->exists($path)) {
                            $request->source->delete($path);
                            ++$deletedFromSource;
                        }

                        continue;
                    }

                    if (!$request->source->exists($path)) {
                        ++$missingInSource;

                        continue;
                    }

                    if ($request->dryRun) {
                        ++$migrated;

                        continue;
                    }

                    $this->copier->copy($path, $request->source, $request->target);
                    ++$migrated;

                    if ($request->deleteSource) {
                        $request->source->delete($path);
                        ++$deletedFromSource;
                    }
                } catch (\Throwable $exception) {
                    $failures[] = sprintf('%s: %s', (string) $media->id(), $exception->getMessage());
                }
            }

            ++$page;
        }

        return new MigrateMediaStorageResponse(
            migrated: $migrated,
            alreadyInTarget: $alreadyInTarget,
            missingInSource: $missingInSource,
            failures: $failures,
            deletedFromSource: $deletedFromSource,
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof MigrateMediaStorageRequest;
    }
}
