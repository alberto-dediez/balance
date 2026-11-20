<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Domain\Model\Folder;

use App\Library\Infrastructure\Persistence\InMemory\Repository\InMemoryFolderRepository;

class FolderRepositoryMother
{
    public static function inMemory(): InMemoryFolderRepository
    {
        return new InMemoryFolderRepository();
    }
}
