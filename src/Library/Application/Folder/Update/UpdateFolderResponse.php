<?php

declare(strict_types=1);

namespace App\Library\Application\Folder\Update;

use App\Library\Domain\Model\Folder\FolderId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateFolderResponse implements CommandResponse
{
    public function __construct(
        public FolderId $folderId,
        public string $name,
    ) {}
}
