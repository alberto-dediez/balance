<?php

namespace App\Tests\Unit\Property\Infrastructure\Persistence\Doctrine\Repository;

use App\Property\Domain\Model\Destination\DestinationRepository;
use App\Property\Infrastructure\Persistence\Doctrine\Repository\DoctrineDestinationRepository;
use App\Tests\Unit\Property\Domain\Model\Destination\DestinationRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineDestinationRepositoryTest extends DestinationRepositoryTest
{
    public function createSUT(): DestinationRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineDestinationRepository($manager);
    }
}
