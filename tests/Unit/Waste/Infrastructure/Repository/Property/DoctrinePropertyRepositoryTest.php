<?php

namespace App\Tests\Unit\Waste\Infrastructure\Repository\Property;

use Doctrine\ORM\EntityManagerInterface;
use App\Security\Domain\Model\Account\Account;
use App\Waste\Domain\Model\Property\PropertyRepository;
use App\Tests\Unit\Waste\Domain\Model\Property\PropertyRepositoryTest;
use App\Waste\Infrastructure\Repository\Property\DoctrinePropertyRepository;
use App\Security\Infrastructure\Repository\Account\DoctrineAccountRepository;

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