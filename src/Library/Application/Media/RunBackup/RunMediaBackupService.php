<?php

declare(strict_types=1);

namespace App\Library\Application\Media\RunBackup;

use App\Library\Application\Media\MediaStorageFileCopier;
use App\Library\Domain\Model\Backup\MediaBackup;
use App\Library\Domain\Model\Backup\MediaBackupFailed;
use App\Library\Domain\Model\Backup\MediaBackupRepository;
use App\Library\Domain\Model\Media\Media;
use App\Library\Domain\Model\Media\MediaFilter;
use App\Library\Domain\Model\Media\MediaRepository;
use App\Library\Domain\Model\Media\MediaStorage;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Application\Persistence\UnitOfWorkReleaser;
use App\Shared\Domain\Event\DomainEventPublisher;
use Psr\Log\LoggerInterface;

class RunMediaBackupService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    private int $copied = 0;

    private int $alreadyBackedUp = 0;

    private int $missingInSource = 0;

    private int $purged = 0;

    /** @var list<string> */
    private array $failures = [];

    public function __construct(
        private readonly MediaRepository $mediaRepository,
        private readonly MediaBackupRepository $backups,
        private readonly MediaStorage $source,
        private readonly MediaStorage $backup,
        private readonly MediaStorageFileCopier $copier,
        private readonly LoggerInterface $logger,
        private readonly UnitOfWorkReleaser $unitOfWorkReleaser,
        private readonly bool $enabled,
        private readonly int $retentionDays,
    ) {}

    public function __invoke(RunMediaBackupRequest $request, User $user): RunMediaBackupResponse
    {
        $this->copied = 0;
        $this->alreadyBackedUp = 0;
        $this->missingInSource = 0;
        $this->purged = 0;
        $this->failures = [];

        if (!$this->enabled) {
            return $this->response(false);
        }

        try {
            $completed = $this->copy($request);

            if ($completed && $this->retentionDays > 0) {
                $this->purge($request);
            }
        } catch (\Throwable $exception) {
            $this->logger->error('Library backup run failed', [
                'exception' => $exception,
            ]);

            DomainEventPublisher::instance()->publish(new MediaBackupFailed(count($this->failures) + 1, $exception->getMessage()));

            throw $exception;
        }

        if ([] !== $this->failures) {
            DomainEventPublisher::instance()->publish(new MediaBackupFailed(count($this->failures), $this->failures[0]));
        }

        $this->logger->info('Library backup run', [
            'copied' => $this->copied,
            'alreadyBackedUp' => $this->alreadyBackedUp,
            'missingInSource' => $this->missingInSource,
            'purged' => $this->purged,
            'failed' => count($this->failures),
        ]);

        return $this->response(true);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RunMediaBackupRequest;
    }

    private function copy(RunMediaBackupRequest $request): bool
    {
        $maxFailures = max(1, $request->maxFailures);
        $filter = new MediaFilter(limit: max(1, $request->batchSize));
        $page = 0;

        while (true) {
            $filter->page = $page;

            $batch = $this->mediaRepository->findByFilter($filter);

            if ([] === $batch) {
                return true;
            }

            $existing = array_flip($this->backups->findExistingPaths(array_map(
                static fn(Media $media): string => $media->path(),
                $batch,
            )));

            foreach ($batch as $media) {
                $path = $media->path();

                if (isset($existing[$path])) {
                    ++$this->alreadyBackedUp;

                    continue;
                }

                try {
                    if (!$this->source->exists($path)) {
                        ++$this->missingInSource;

                        continue;
                    }

                    $this->copier->copy($path, $this->source, $this->backup);
                    $this->backups->save(new MediaBackup($path, (string) $media->id(), $request->now));
                    ++$this->copied;
                } catch (\Throwable $exception) {
                    $this->recordFailure((string) $media->id(), $exception);

                    if (count($this->failures) >= $maxFailures) {
                        return false;
                    }
                }
            }

            $this->unitOfWorkReleaser->release();

            ++$page;
        }
    }

    private function purge(RunMediaBackupRequest $request): void
    {
        $limit = $request->now->modify(sprintf('-%d days', $this->retentionDays));

        while (true) {
            $expired = $this->backups->findRemovedBefore($limit, max(1, $request->batchSize));

            if ([] === $expired) {
                return;
            }

            foreach ($expired as $backup) {
                try {
                    $this->backup->delete($backup->path());
                    $this->backups->remove($backup);
                    ++$this->purged;
                } catch (\Throwable $exception) {
                    $this->recordFailure($backup->mediaId(), $exception);

                    return;
                }
            }

            $this->unitOfWorkReleaser->release();
        }
    }

    private function recordFailure(string $mediaId, \Throwable $exception): void
    {
        $failure = sprintf('%s: %s', $mediaId, $exception->getMessage());

        $this->failures[] = $failure;

        $this->logger->error('Library backup failure', [
            'mediaId' => $mediaId,
            'exception' => $exception,
        ]);
    }

    private function response(bool $enabled): RunMediaBackupResponse
    {
        return new RunMediaBackupResponse(
            enabled: $enabled,
            copied: $this->copied,
            alreadyBackedUp: $this->alreadyBackedUp,
            missingInSource: $this->missingInSource,
            purged: $this->purged,
            failures: $this->failures,
        );
    }
}
