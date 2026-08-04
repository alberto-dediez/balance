<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\CreditPurchase;

use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryCreditPurchaseRepository;

class CreditPurchaseRepositoryMother
{
    public static function inMemory(): InMemoryCreditPurchaseRepository
    {
        return new InMemoryCreditPurchaseRepository();
    }
}
