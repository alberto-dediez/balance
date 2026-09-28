<?php

declare(strict_types=1);

namespace App\Changelog\Application\Announcement\DownloadManual;

use App\Library\Domain\Model\Media\MediaId;
use App\Shared\Application\CommandBus\CommandResponse;

class DownloadAnnouncementManualResponse implements CommandResponse
{
    public function __construct(public MediaId $mediaId) {}
}
