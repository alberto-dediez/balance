<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository\DoctrinePropertyCarbonFootprintRepository;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrinePropertyCarbonFootprintRepositoryTest extends PropertyCarbonFootprintRepositoryTest
{
    protected function createSUT(): PropertyCarbonFootprintRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrinePropertyCarbonFootprintRepository($manager);
    }
}
