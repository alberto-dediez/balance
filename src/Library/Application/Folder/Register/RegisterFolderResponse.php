<?php

declare(strict_types=1);

namespace App\Library\Application\Folder\Register;

use App\Library\Domain\Model\Folder\FolderId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterFolderResponse implements CommandResponse
{
    public function __construct(
        public FolderId $folderId,
        public string $name,
    ) {}
}
