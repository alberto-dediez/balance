<?php

declare(strict_types=1);

namespace App\Library\Application\Media\Upload;

use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaType;
use App\Library\Domain\Model\Media\MediaVisibility;
use App\Shared\Application\CommandBus\CommandResponse;

class UploadMediaResponse implements CommandResponse
{
    public function __construct(
        public MediaId $mediaId,
        public string $name,
        public string $fileName,
        public string $path,
        public string $mimeType,
        public MediaType $type,
        public int $size,
        public MediaVisibility $visibility = MediaVisibility::Account,
        public bool $shared = false,
    ) {}
}
