<?php

declare(strict_types=1);

namespace App\Library\Domain\Model\Folder;

class FolderNestingNotAllowedException extends \RuntimeException
{
    public function __construct(FolderId $parentId)
    {
        parent::__construct(sprintf('Folder "%s" is a subfolder and cannot contain folders', (string) $parentId));
    }
}
