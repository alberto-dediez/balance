<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Infrastructure\Persistence\Doctrine\Repository;

use App\Library\Domain\Model\Media\MediaRepository;
use App\Library\Infrastructure\Persistence\Doctrine\Repository\DoctrineMediaRepository;
use App\Tests\Unit\Library\Domain\Model\Media\MediaRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineMediaRepositoryTest extends MediaRepositoryTest
{
    public function createSUT(): MediaRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineMediaRepository($manager);
    }
}
