<?php

declare(strict_types=1);

namespace App\Library\Application\Folder\Remove;

use App\Library\Domain\Model\Folder\FolderId;
use App\Shared\Application\CommandBus\CommandResponse;

class RemoveFolderResponse implements CommandResponse
{
    public function __construct(
        public FolderId $folderId,
        public int $removedMedia = 0,
        public int $removedSubfolders = 0,
    ) {}
}
