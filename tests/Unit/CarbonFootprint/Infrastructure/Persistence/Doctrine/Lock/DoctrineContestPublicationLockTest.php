<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Persistence\Doctrine\Lock;

use App\CarbonFootprint\Infrastructure\Persistence\Doctrine\Lock\DoctrineContestPublicationLock;
use App\Security\Infrastructure\Persistence\Doctrine\Repository\DoctrineAccountRepository;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\TransactionRequiredException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DoctrineContestPublicationLockTest extends KernelTestCase
{
    public function testAcquiresTheAccountRowInsideATransaction(): void
    {
        $account = AccountMother::registeredAccount();
        (new DoctrineAccountRepository($this->manager()))->add($account);
        $connection = $this->manager()->getConnection();

        $connection->beginTransaction();

        try {
            (new DoctrineContestPublicationLock($this->manager()))->acquire($account->id());

            $this->assertTrue($connection->isTransactionActive());
        } finally {
            $connection->rollBack();
        }
    }

    public function testRequiresATransaction(): void
    {
        $account = AccountMother::registeredAccount();
        (new DoctrineAccountRepository($this->manager()))->add($account);

        $this->expectException(TransactionRequiredException::class);

        (new DoctrineContestPublicationLock($this->manager()))->acquire($account->id());
    }

    private function manager(): EntityManagerInterface
    {
        return $this->getContainer()->get(EntityManagerInterface::class);
    }
}
