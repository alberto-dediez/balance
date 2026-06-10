<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Infrastructure\Persistence\Doctrine\Repository;

use App\Tests\Unit\Waste\Domain\Model\AlertConfiguration\AlertConfigurationRepositoryTest;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationRepository;
use App\Waste\Infrastructure\Persistence\Doctrine\Repository\DoctrineAlertConfigurationRepository;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineAlertConfigurationRepositoryTest extends AlertConfigurationRepositoryTest
{
    protected function createSUT(): AlertConfigurationRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineAlertConfigurationRepository($manager);
    }
}
