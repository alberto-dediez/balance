<?php

declare(strict_types=1);

namespace App\Payments\Application\Payout\Create;

interface PayoutCreationLock
{
    /**
     * @template T
     *
     * @param callable(): T $creation
     *
     * @return T
     */
    public function withLock(callable $creation): mixed;
}
