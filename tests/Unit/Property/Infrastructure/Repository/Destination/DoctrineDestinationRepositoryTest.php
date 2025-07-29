<?php

namespace App\Tests\Unit\Property\Infrastructure\Repository\Destination;

use Doctrine\ORM\EntityManagerInterface;
use App\Property\Domain\Model\Destination\DestinationRepository;
use App\Tests\Unit\Property\Domain\Model\Destination\DestinationRepositoryTest;
use App\Property\Infrastructure\Repository\Destination\DoctrineDestinationRepository;

class DoctrineDestinationRepositoryTest extends DestinationRepositoryTest
{
    public function createSUT(): DestinationRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineDestinationRepository($manager);
    }
}