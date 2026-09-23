<?php

declare(strict_types=1);

namespace App\Changelog\Application\Announcement\DownloadManual;

use App\Shared\Application\CommandBus\CommandResponse;

class DownloadAnnouncementManualResponse implements CommandResponse
{
    /**
     * @param resource $stream
     */
    public function __construct(
        public mixed $stream,
        public string $fileName,
        public int $size,
        public string $mimeType = 'application/pdf',
    ) {}
}
