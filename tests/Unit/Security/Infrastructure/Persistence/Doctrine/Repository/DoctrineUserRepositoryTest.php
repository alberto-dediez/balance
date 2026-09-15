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
        $accountRepository->method('find')->willReturnCallback(function ($id) use ($manager): ?Account {
            if (null === $id) {
                return null;
            }

            $account = $manager->find(Account::class, $id);

            if ($account instanceof Account) {
                return $account;
            }

            $account = new Account($id);
            $account->register('account', true);

            $manager->persist($account);
            $manager->flush();

            return $account;
        });

        /* @var DoctrineAccountRepository&MockObject $accountRepository */

        return new DoctrineUserRepository($manager, $accountRepository);
    }
}
