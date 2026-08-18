<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Persistence\InMemory\Lock;

use App\Payments\Application\Payout\Create\PayoutCreationLock;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryPayoutCreationLock implements PayoutCreationLock
{
    /**
     * @template T
     *
     * @param callable(): T $creation
     *
     * @return T
     */
    public function withLock(callable $creation): mixed
    {
        return $creation();
    }
}
