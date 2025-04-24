<?php

namespace App\Tests\Unit\Waste\Infrastructure\Repository\WasteManager;

use Doctrine\ORM\EntityManagerInterface;
use App\Waste\Domain\Model\WasteManager\WasteManagerRepository;
use App\Tests\Unit\Waste\Domain\Model\WasteManager\WasteManagerRepositoryTest;
use App\Waste\Infrastructure\Repository\WasteManager\DoctrineWasteManagerRepository;

class DoctrineWasteManagerRepositoryTest extends WasteManagerRepositoryTest
{
    public function createSUT(): WasteManagerRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineWasteManagerRepository($manager);
    }
}