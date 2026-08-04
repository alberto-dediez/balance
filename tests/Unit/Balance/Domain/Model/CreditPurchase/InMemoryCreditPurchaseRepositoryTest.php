<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\CreditPurchase;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;

class InMemoryCreditPurchaseRepositoryTest extends CreditPurchaseRepositoryTest
{
    protected function createSUT(): CreditPurchaseRepository
    {
        return CreditPurchaseRepositoryMother::inMemory();
    }
}
