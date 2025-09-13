<?php

namespace App\Tests\Unit\Security\Infrastructure\Repository\User;

use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\User\UserRepository;
use App\Security\Infrastructure\Persistence\Doctrine\Repository\DoctrineAccountRepository;
use App\Security\Infrastructure\Persistence\Doctrine\Repository\DoctrineUserRepository;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineUserRepositoryTest extends UserRepositoryTest
{
    public function createSUT(): UserRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        $accountRepository = $this->createMock(DoctrineAccountRepository::class);
        $accountRepository->method('find')->willReturnCallback(function ($id) {
            return null !== $id ? new Account($id) : null;
        });

        /** @var DoctrineAccountRepository $accountRepository */

        return new DoctrineUserRepository($manager, $accountRepository);
    }
}
