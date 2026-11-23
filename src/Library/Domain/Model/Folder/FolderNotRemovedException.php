<?php

declare(strict_types=1);

namespace App\Library\Domain\Model\Folder;

class FolderNotRemovedException extends \RuntimeException
{
    public function __construct(FolderId $folderId, private readonly int $failedMedia)
    {
        parent::__construct(sprintf('Folder "%s" was not removed: %d files could not be removed', (string) $folderId, $failedMedia));
    }

    public function failedMedia(): int
    {
        return $this->failedMedia;
    }
}
