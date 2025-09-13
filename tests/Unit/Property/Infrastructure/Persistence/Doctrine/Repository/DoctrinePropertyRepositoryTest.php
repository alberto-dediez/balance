<?php

namespace App\Tests\Unit\Property\Infrastructure\Persistence\Doctrine\Repository;

use App\Property\Domain\Model\Property\PropertyRepository;
use App\Property\Infrastructure\Repository\Property\DoctrinePropertyRepository;
use App\Security\Domain\Model\Account\Account;
use App\Security\Infrastructure\Persistence\Doctrine\Repository\DoctrineAccountRepository;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrinePropertyRepositoryTest extends PropertyRepositoryTest
{
    public function createSUT(): PropertyRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        $accountRepository = $this->createMock(DoctrineAccountRepository::class);
        $accountRepository->method('find')->willReturnCallback(function ($id) {
            return null !== $id ? new Account($id) : null;
        });

        return new DoctrinePropertyRepository($manager, $accountRepository);
    }
}
