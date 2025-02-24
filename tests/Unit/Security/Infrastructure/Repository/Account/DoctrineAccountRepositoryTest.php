<?php

namespace App\Tests\Unit\Security\Infrastructure\Repository\Account;

use App\Security\Domain\Model\Account\AccountRepository;
use App\Tests\Unit\Security\Domain\Model\Account\AccountRepositoryTest;
use App\Security\Infrastructure\Repository\Account\DoctrineAccountRepository;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineAccountRepositoryTest extends AccountRepositoryTest
{
    public function createSUT(): AccountRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineAccountRepository($manager);
    }
}