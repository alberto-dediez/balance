<?php

declare(strict_types=1);

namespace App\Tests\Unit\HumanResources\Infrastructure\Persistence\Doctrine\Repository;

use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\HumanResources\Infrastructure\Persistence\Doctrine\Repository\DoctrineEmployeeRepository;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineEmployeeRepositoryTest extends EmployeeRepositoryTest
{
    protected function createSUT(): EmployeeRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineEmployeeRepository($manager);
    }
}
