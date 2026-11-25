<?php

declare(strict_types=1);

namespace App\Library\Application\Media\ExtractBackup;

use App\Library\Domain\Model\Backup\MediaBackupNotFoundException;
use App\Library\Domain\Model\Backup\MediaBackupRepository;
use App\Library\Domain\Model\Media\MediaStorage;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class ExtractMediaBackupService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly MediaBackupRepository $backups,
        private readonly MediaStorage $backup,
    ) {}

    public function __invoke(ExtractMediaBackupRequest $request, User $user): ExtractMediaBackupResponse
    {
        $backup = $this->backups->findByMediaId($request->mediaId);

        if (null === $backup) {
            throw new MediaBackupNotFoundException($request->mediaId);
        }

        if (file_exists($request->outputPath)) {
            throw new \InvalidArgumentException(sprintf('The file "%s" already exists', $request->outputPath));
        }

        $stream = $this->backup->read($backup->path());
        $output = @fopen($request->outputPath, 'xb');

        if (false === $output) {
            fclose($stream);

            throw new \InvalidArgumentException(sprintf('Unable to write the file "%s"', $request->outputPath));
        }

        try {
            stream_copy_to_stream($stream, $output);
        } finally {
            fclose($stream);
            fclose($output);
        }

        return new ExtractMediaBackupResponse(
            path: $backup->path(),
            outputPath: $request->outputPath,
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ExtractMediaBackupRequest;
    }
}
