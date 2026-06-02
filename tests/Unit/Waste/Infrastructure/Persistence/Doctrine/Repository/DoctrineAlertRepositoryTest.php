<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Infrastructure\Persistence\Doctrine\Repository;

use App\Tests\Unit\Waste\Domain\Model\Alert\AlertRepositoryTest;
use App\Waste\Domain\Model\Alert\AlertRepository;
use App\Waste\Infrastructure\Persistence\Doctrine\Repository\DoctrineAlertRepository;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineAlertRepositoryTest extends AlertRepositoryTest
{
    protected function createSUT(): AlertRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineAlertRepository($manager);
    }
}
