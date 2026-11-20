<?php

declare(strict_types=1);

namespace App\Library\Application\Folder\Remove;

use App\Library\Domain\Model\Folder\FolderId;
use App\Shared\Application\CommandBus\Command;

class RemoveFolderRequest implements Command
{
    public function __construct(public FolderId $folderId) {}
}
