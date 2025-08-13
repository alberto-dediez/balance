<?php

namespace App\Tests\Unit\Waste\Infrastructure\Repository\DailyWaste;

use Doctrine\ORM\EntityManagerInterface;
use App\Waste\Domain\Model\DailyWaste\DailyWasteRepository;
use App\Tests\Unit\Waste\Domain\Model\DailyWaste\DailyWasteRepositoryTest;
use App\Waste\Infrastructure\Repository\DailyWaste\DoctrineDailyWasteRepository;

class DoctrineDailyWasteRepositoryTest extends DailyWasteRepositoryTest
{
    public function createSUT(): DailyWasteRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineDailyWasteRepository($manager);
    }
}