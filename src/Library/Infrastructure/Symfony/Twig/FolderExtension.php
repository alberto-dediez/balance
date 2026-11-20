<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\Symfony\Twig;

use App\Library\Application\Folder\CurrentAccountFolders;
use App\Library\Domain\Model\Folder\Folder;
use App\Library\Domain\Model\Folder\FolderId;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class FolderExtension extends AbstractExtension
{
    public function __construct(private readonly CurrentAccountFolders $folders) {}

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('folder_name', $this->folderName(...)),
        ];
    }

    public function folderName(string|FolderId $folderId): string
    {
        $folder = $this->folders->find($folderId instanceof FolderId ? $folderId : new FolderId($folderId));

        return $folder instanceof Folder ? $folder->name() : '';
    }
}
