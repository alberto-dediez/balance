<?php

declare(strict_types=1);

namespace App\Shared\Application\Transaction;

interface TransactionalSession
{
    /**
     * @template T
     *
     * @param callable(): T $operation
     *
     * @return T
     */
    public function run(callable $operation): mixed;
}
