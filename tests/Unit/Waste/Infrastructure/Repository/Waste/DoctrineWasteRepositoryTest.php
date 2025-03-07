<?php

namespace App\Tests\Unit\Waste\Infrastructure\Repository\Waste;

use App\Waste\Domain\Model\Waste\WasteRepository;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteRepositoryTest;
use App\Waste\Infrastructure\Repository\Waste\DoctrineWasteRepository;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineWasteRepositoryTest extends WasteRepositoryTest
{
    public function createSUT(): WasteRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineWasteRepository($manager);
    }
}