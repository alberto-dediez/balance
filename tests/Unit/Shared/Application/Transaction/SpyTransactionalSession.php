<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Transaction;

use App\Shared\Application\Transaction\TransactionalSession;

class SpyTransactionalSession implements TransactionalSession
{
    private int $runs = 0;
    private int $rolledBack = 0;
    private bool $active = false;

    /**
     * @template T
     *
     * @param callable(): T $operation
     *
     * @return T
     */
    public function run(callable $operation): mixed
    {
        ++$this->runs;
        $this->active = true;

        try {
            return $operation();
        } catch (\Throwable $exception) {
            ++$this->rolledBack;

            throw $exception;
        } finally {
            $this->active = false;
        }
    }

    public function runs(): int
    {
        return $this->runs;
    }

    public function rolledBack(): int
    {
        return $this->rolledBack;
    }

    public function isActive(): bool
    {
        return $this->active;
    }
}
