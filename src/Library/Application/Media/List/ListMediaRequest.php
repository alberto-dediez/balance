<?php

declare(strict_types=1);

namespace App\Library\Application\Media\List;

use App\Library\Domain\Model\Folder\FolderId;
use App\Library\Domain\Model\Media\MediaFilter;

class ListMediaRequest extends MediaFilter
{
    public const ALL_FOLDERS = 'all';

    public const WITH_SUBFOLDERS_PREFIX = 'all:';

    public ?string $folder = null;

    public bool $searchInFolderNames = false;

    public function includesSubfolders(): bool
    {
        return null !== $this->folder && str_starts_with($this->folder, self::WITH_SUBFOLDERS_PREFIX);
    }

    public function chosenFolderId(): ?FolderId
    {
        if (null === $this->folder || self::ALL_FOLDERS === $this->folder) {
            return null;
        }

        if ($this->includesSubfolders()) {
            return new FolderId(substr($this->folder, strlen(self::WITH_SUBFOLDERS_PREFIX)));
        }

        return new FolderId($this->folder);
    }

    /**
     * @param list<FolderId> $subfolderIds
     */
    public function applyFolderChoice(array $subfolderIds = []): void
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

        $chosenFolderId = $this->chosenFolderId();

        $this->folderIds = null === $chosenFolderId ? [] : [$chosenFolderId];

        if ($this->includesSubfolders()) {
            $this->folderIds = [...$this->folderIds, ...$subfolderIds];
        }

        $this->withoutFolder = null;
    }
}
