<?php

declare(strict_types=1);

namespace App\Library\Application\Media;

use App\Library\Domain\Model\Media\MediaStorage;
use App\Library\Domain\Model\Media\NotStoredMediaException;

final class MediaStorageFileCopier
{
    public function copy(string $path, MediaStorage $source, MediaStorage $target): void
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'media-migrate');

        if (false === $temporaryPath) {
            throw new NotStoredMediaException('Unable to create a temporary file');
        }

        try {
            $stream = $source->read($path);
            $temporary = fopen($temporaryPath, 'wb');

            if (false === $temporary) {
                fclose($stream);

                throw new NotStoredMediaException(sprintf('Unable to write the temporary file "%s"', $temporaryPath));
            }

            stream_copy_to_stream($stream, $temporary);
            fclose($stream);
            fclose($temporary);

            $target->write($path, $temporaryPath);
        } finally {
            @unlink($temporaryPath);
        }
    }
}
