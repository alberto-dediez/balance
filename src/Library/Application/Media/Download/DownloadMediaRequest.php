<?php

declare(strict_types=1);

namespace App\Library\Application\Media\Download;

use App\Library\Domain\Model\Media\MediaId;

class DownloadMediaRequest
{
    public function __construct(public MediaId $mediaId) {}
}
