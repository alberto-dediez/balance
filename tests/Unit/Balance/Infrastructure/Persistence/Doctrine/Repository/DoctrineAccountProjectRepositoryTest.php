<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Infrastructure\Persistence\Doctrine\Repository;

use App\Balance\Domain\Model\AccountProject\AccountProjectRepository;
use App\Balance\Infrastructure\Persistence\Doctrine\Repository\DoctrineAccountProjectRepository;
use App\Tests\Unit\Balance\Domain\Model\AccountProject\AccountProjectRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineAccountProjectRepositoryTest extends AccountProjectRepositoryTest
{
    protected function createSUT(): AccountProjectRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineAccountProjectRepository($manager);
    }
}
