<?php

declare(strict_types=1);

namespace App\Changelog\Application\ChangelogEntry\DownloadManual;

class DownloadManualResponse
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
