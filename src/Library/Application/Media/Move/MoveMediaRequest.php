<?php

declare(strict_types=1);

namespace App\Library\Application\Media\Move;

use App\Library\Domain\Model\Folder\FolderId;
use App\Library\Domain\Model\Media\MediaId;
use App\Shared\Application\CommandBus\Command;

class MoveMediaRequest implements Command
{
    public function __construct(
        public MediaId $mediaId,
        public ?FolderId $folderId = null,
    ) {}
}
