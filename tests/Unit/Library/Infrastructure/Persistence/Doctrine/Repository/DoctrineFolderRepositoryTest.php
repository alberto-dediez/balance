<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Infrastructure\Persistence\Doctrine\Repository;

use App\Library\Domain\Model\Folder\FolderRepository;
use App\Library\Infrastructure\Persistence\Doctrine\Repository\DoctrineFolderRepository;
use App\Tests\Unit\Library\Domain\Model\Folder\FolderRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineFolderRepositoryTest extends FolderRepositoryTest
{
    protected function createSUT(): FolderRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineFolderRepository($manager);
    }
}
