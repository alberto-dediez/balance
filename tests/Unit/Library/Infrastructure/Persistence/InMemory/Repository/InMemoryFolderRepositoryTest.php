<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Infrastructure\Persistence\InMemory\Repository;

use App\Library\Domain\Model\Folder\FolderRepository;
use App\Tests\Unit\Library\Domain\Model\Folder\FolderRepositoryMother;
use App\Tests\Unit\Library\Domain\Model\Folder\FolderRepositoryTest;

class InMemoryFolderRepositoryTest extends FolderRepositoryTest
{
    protected function createSUT(): FolderRepository
    {
        return FolderRepositoryMother::inMemory();
    }
}
