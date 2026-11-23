<?php

declare(strict_types=1);

namespace App\Library\Application\Media\List;

use App\Library\Domain\Model\Folder\FolderId;
use App\Library\Domain\Model\Media\MediaFilter;

class ListMediaRequest extends MediaFilter
{
    public const ALL_FOLDERS = 'all';

    public ?string $folder = null;

    public bool $searchInFolderNames = false;

    public function applyFolderChoice(): void
    {
        if (null === $this->folder) {
            $this->folderIds = [];
            $this->withoutFolder = true;

            return;
        }

        if (self::ALL_FOLDERS === $this->folder) {
            $this->folderIds = [];
            $this->withoutFolder = null;

            return;
        }

        $this->folderIds = [new FolderId($this->folder)];
        $this->withoutFolder = null;
    }
}
