<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventOperations\Infrastructure\Persistence\Doctrine\Repository;

use App\EventOperations\Domain\Model\Event\EventRepository;
use App\EventOperations\Infrastructure\Persistence\Doctrine\Repository\DoctrineEventRepository;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineEventRepositoryTest extends EventRepositoryTest
{
    protected function createSUT(): EventRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineEventRepository($manager);
    }
}
