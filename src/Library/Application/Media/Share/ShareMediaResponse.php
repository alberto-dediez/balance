<?php

declare(strict_types=1);

namespace App\Library\Application\Media\Share;

use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaVisibility;
use App\Shared\Application\CommandBus\CommandResponse;

class ShareMediaResponse implements CommandResponse
{
    public function __construct(
        public MediaId $mediaId,
        public MediaVisibility $visibility = MediaVisibility::Account,
    ) {}
}
