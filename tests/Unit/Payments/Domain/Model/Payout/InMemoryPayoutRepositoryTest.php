<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Domain\Model\Payout;

use App\Payments\Domain\Model\Payout\PayoutRepository;

class InMemoryPayoutRepositoryTest extends PayoutRepositoryTest
{
    protected function createSUT(): PayoutRepository
    {
        return PayoutRepositoryMother::inMemory();
    }
}
