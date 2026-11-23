<?php

declare(strict_types=1);

namespace App\Library\Application\Folder\List;

use App\Library\Domain\Model\Folder\Folder;

class ListFoldersResponse
{
    /**
     * @param Folder[] $folders
     */
    public function __construct(public array $folders = [], public int $totalResults = 0) {}
}
