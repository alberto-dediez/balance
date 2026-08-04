<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Infrastructure\Persistence\Doctrine\Repository;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Infrastructure\Persistence\Doctrine\Repository\DoctrineCreditPurchaseRepository;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineCreditPurchaseRepositoryTest extends CreditPurchaseRepositoryTest
{
    protected function createSUT(): CreditPurchaseRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrineCreditPurchaseRepository($manager);
    }
}
