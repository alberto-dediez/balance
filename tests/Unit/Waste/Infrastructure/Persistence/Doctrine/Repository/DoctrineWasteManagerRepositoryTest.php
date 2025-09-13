<?php

namespace App\Tests\Unit\Waste\Infrastructure\Persistence\Doctrine\Repository;

use App\Tests\Unit\Waste\Domain\Model\WasteManager\WasteManagerRepositoryTest;
use App\Waste\Domain\Model\WasteManager\WasteManagerRepository;
use App\Waste\Infrastructure\Repository\WasteManager\DoctrineWasteManagerRepository;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineWasteManagerRepositoryTest extends WasteManagerRepositoryTest
{
    public function createSUT(): WasteManagerRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineWasteManagerRepository($manager);
    }
}
