<?php

namespace App\Tests\Unit\Waste\Infrastructure\Persistence\Doctrine\Repository;

use App\Tests\Unit\Waste\Domain\Model\DailyWaste\DailyWasteRepositoryTest;
use App\Waste\Domain\Model\DailyWaste\DailyWasteRepository;
use App\Waste\Infrastructure\Repository\DailyWaste\DoctrineDailyWasteRepository;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineDailyWasteRepositoryTest extends DailyWasteRepositoryTest
{
    public function createSUT(): DailyWasteRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineDailyWasteRepository($manager);
    }
}
