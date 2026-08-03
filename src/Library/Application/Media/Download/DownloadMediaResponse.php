<?php

declare(strict_types=1);

namespace App\Library\Application\Media\Download;

class DownloadMediaResponse
{
    /**
     * @param resource $stream
     */
    public function __construct(
        public mixed $stream,
        public string $fileName,
        public string $mimeType,
        public int $size,
    ) {}
}
