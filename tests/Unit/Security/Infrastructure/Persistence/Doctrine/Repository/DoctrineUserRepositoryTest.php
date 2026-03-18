<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Infrastructure\Persistence\Doctrine\Repository;

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
        $accountRepository->method('find')->willReturnCallback(fn($id): ?Account => null !== $id ? new Account($id) : null);

        /* @var DoctrineAccountRepository&MockObject $accountRepository */

        return new DoctrineUserRepository($manager, $accountRepository);
    }
}
