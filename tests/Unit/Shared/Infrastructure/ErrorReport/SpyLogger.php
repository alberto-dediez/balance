<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\ErrorReport;

use Psr\Log\AbstractLogger;

class SpyLogger extends AbstractLogger
{
    /** @var list<array{level: string, message: string}> */
    private array $entries = [];

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->entries[] = ['level' => (string) $level, 'message' => (string) $message];
    }

    /**
     * @return list<string>
     */
    public function levels(): array
    {
        return array_column($this->entries, 'level');
    }

    /**
     * @return list<array{level: string, message: string}>
     */
    public function entries(): array
    {
        return $this->entries;
    }
}
