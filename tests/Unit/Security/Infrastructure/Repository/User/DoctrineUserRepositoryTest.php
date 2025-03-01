<?php

namespace App\Tests\Unit\Security\Infrastructure\Repository\User;

use Doctrine\ORM\EntityManagerInterface;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\User\UserRepository;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryTest;
use App\Security\Infrastructure\Repository\User\DoctrineUserRepository;
use App\Security\Infrastructure\Repository\Account\DoctrineAccountRepository;

class DoctrineUserRepositoryTest extends UserRepositoryTest
{
    public function createSUT(): UserRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        $accountRepository = $this->createMock(DoctrineAccountRepository::class);
        $accountRepository->method('find')->willReturnCallback(function ($id) {
            return null !== $id ? new Account($id) : null;
        });

        return new DoctrineUserRepository($manager, $accountRepository);
    }
}