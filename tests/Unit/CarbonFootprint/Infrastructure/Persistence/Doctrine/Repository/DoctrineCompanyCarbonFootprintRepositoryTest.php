<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository;

use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Repository\DoctrineCompanyCarbonFootprintRepository;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineCompanyCarbonFootprintRepositoryTest extends CompanyCarbonFootprintRepositoryTest
{
    protected function createSUT(): CompanyCarbonFootprintRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineCompanyCarbonFootprintRepository($manager);
    }
}
