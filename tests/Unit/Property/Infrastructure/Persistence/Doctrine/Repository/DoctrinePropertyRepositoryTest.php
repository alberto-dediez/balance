<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Infrastructure\Persistence\Doctrine\Repository;

use App\Property\Domain\Model\Property\PropertyRepository;
use App\Property\Infrastructure\Persistence\Doctrine\Repository\DoctrinePropertyRepository;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrinePropertyRepositoryTest extends PropertyRepositoryTest
{
    public function createSUT(): PropertyRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrinePropertyRepository($manager);
    }
}
