<?php

namespace App\Tests\Unit\Security\Infrastructure\Repository\Account;

use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Infrastructure\Persistence\Doctrine\Repository\DoctrineAccountRepository;
use App\Tests\Unit\Security\Domain\Model\Account\AccountRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineAccountRepositoryTest extends AccountRepositoryTest
{
    public function createSUT(): AccountRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineAccountRepository($manager);
    }
}
