<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository\DoctrineCarbonFootprintReportRepository;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineCarbonFootprintReportRepositoryTest extends CarbonFootprintReportRepositoryTest
{
    protected function createSUT(): CarbonFootprintReportRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineCarbonFootprintReportRepository($manager);
    }
}
