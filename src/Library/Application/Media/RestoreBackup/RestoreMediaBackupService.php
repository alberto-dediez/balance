<?php

declare(strict_types=1);

namespace App\Library\Application\Media\RestoreBackup;

use App\Library\Application\Media\MediaStorageFileCopier;
use App\Library\Domain\Model\Media\Media;
use App\Library\Domain\Model\Media\MediaFilter;
use App\Library\Domain\Model\Media\MediaNotFoundException;
use App\Library\Domain\Model\Media\MediaRepository;
use App\Library\Domain\Model\Media\MediaStorage;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Application\Persistence\UnitOfWorkReleaser;

class RestoreMediaBackupService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly MediaRepository $mediaRepository,
        private readonly MediaStorage $primary,
        private readonly MediaStorage $backup,
        private readonly MediaStorageFileCopier $copier,
        private readonly UnitOfWorkReleaser $unitOfWorkReleaser,
    ) {}

    public function __invoke(RestoreMediaBackupRequest $request, User $user): RestoreMediaBackupResponse
    {
        $restored = 0;
        $alreadyInPrimary = 0;
        $missingInBackup = 0;
        $failures = [];

        foreach ($this->mediaToRestore($request) as $media) {
            $path = $media->path();

            try {
                if ($this->primary->exists($path)) {
                    ++$alreadyInPrimary;

                    continue;
                }

                if (!$this->backup->exists($path)) {
                    ++$missingInBackup;

                    continue;
                }

                if (!$request->dryRun) {
                    $this->copier->copy($path, $this->backup, $this->primary);
                }

                ++$restored;
            } catch (\Throwable $exception) {
                $failures[] = sprintf('%s: %s', (string) $media->id(), $exception->getMessage());
            }
        }

        return new RestoreMediaBackupResponse(
            restored: $restored,
            alreadyInPrimary: $alreadyInPrimary,
            missingInBackup: $missingInBackup,
            failures: $failures,
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RestoreMediaBackupRequest;
    }

    /**
     * @return iterable<Media>
     */
    private function mediaToRestore(RestoreMediaBackupRequest $request): iterable
    {
        if (null !== $request->mediaId) {
            $media = $this->mediaRepository->find($request->mediaId);

            if (null === $media) {
                throw new MediaNotFoundException($request->mediaId);
            }

            yield $media;

            return;
        }

        $filter = new MediaFilter(limit: max(1, $request->batchSize));
        $page = 0;

        while (true) {
            $filter->page = $page;

            $batch = $this->mediaRepository->findByFilter($filter);

            if ([] === $batch) {
                return;
            }

            yield from $batch;

            $this->unitOfWorkReleaser->release();

            ++$page;
        }
    }
}
