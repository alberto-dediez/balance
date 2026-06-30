<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository\DoctrineEventCarbonFootprintRepository;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineEventCarbonFootprintRepositoryTest extends EventCarbonFootprintRepositoryTest
{
    protected function createSUT(): EventCarbonFootprintRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineEventCarbonFootprintRepository($manager);
    }
}
