<?php

declare(strict_types=1);

namespace App\Library\Domain\Model\Folder;

class FolderNotFoundException extends \RuntimeException
{
    public function __construct(FolderId $folderId)
    {
        parent::__construct(sprintf('Folder "%s" not found', (string) $folderId));
    }
}
