<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\InMemory\Transaction;

use App\Shared\Application\Transaction\TransactionalSession;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryTransactionalSession implements TransactionalSession
{
    /**
     * @template T
     *
     * @param callable(): T $operation
     *
     * @return T
     */
    public function run(callable $operation): mixed
    {
        return $operation();
    }
}
