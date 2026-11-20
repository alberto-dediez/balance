<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\UI\Web\Folder;

use App\Library\Domain\Model\Folder\Folder;
use App\Library\Domain\Model\Folder\FolderId;
use App\Library\Domain\Model\Folder\FolderRepository;

trait GetFolderFromIdTrait
{
    private function getFolderFromId(string $folderId, FolderRepository $folderRepository): Folder
    {
        $folder = $folderRepository->find(new FolderId($folderId));

        if (null === $folder) {
            throw $this->createNotFoundException('Folder not found');
        }

        return $folder;
    }
}
