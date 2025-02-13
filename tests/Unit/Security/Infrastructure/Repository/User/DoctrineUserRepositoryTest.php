<?php

namespace App\Tests\Unit\Security\Infrastructure\Repository\User;

use App\Security\Domain\Model\User\UserRepository;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryTest;
use App\Security\Infrastructure\Repository\User\DoctrineUserRepository;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineUserRepositoryTest extends UserRepositoryTest
{
    public function createSUT(): UserRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineUserRepository($manager);
    }
}