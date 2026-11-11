<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Infrastructure\Persistence\Doctrine\Repository;

use App\Security\Domain\Model\ApiToken\ApiTokenRepository;
use App\Security\Infrastructure\Persistence\Doctrine\Repository\DoctrineApiTokenRepository;
use App\Tests\Unit\Security\Domain\Model\ApiToken\ApiTokenRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineApiTokenRepositoryTest extends ApiTokenRepositoryTest
{
    protected function createSUT(): ApiTokenRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineApiTokenRepository($manager);
    }
}
