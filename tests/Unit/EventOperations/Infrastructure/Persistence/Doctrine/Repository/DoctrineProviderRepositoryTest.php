<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventOperations\Infrastructure\Persistence\Doctrine\Repository;

use App\EventOperations\Domain\Model\Provider\ProviderRepository;
use App\EventOperations\Infrastructure\Persistence\Doctrine\Repository\DoctrineProviderRepository;
use App\Tests\Unit\EventOperations\Domain\Model\Provider\ProviderRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineProviderRepositoryTest extends ProviderRepositoryTest
{
    protected function createSUT(): ProviderRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineProviderRepository($manager);
    }
}
