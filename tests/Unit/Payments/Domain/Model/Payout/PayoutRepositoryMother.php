<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Domain\Model\Payout;

use App\Payments\Infrastructure\Persistence\InMemory\Repository\InMemoryPayoutRepository;

class PayoutRepositoryMother
{
    public static function inMemory(): InMemoryPayoutRepository
    {
        return new InMemoryPayoutRepository();
    }
}
