<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Persistence\Doctrine;

use App\Shared\Infrastructure\Persistence\Doctrine\DatabaseHealthIndicator;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\NullLogger;

class DatabaseHealthIndicatorTest extends TestCase
{
    public function testReachableDatabaseIsHealthy(): void
    {
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);

        $result = (new DatabaseHealthIndicator($connection, new NullLogger()))->check();

        self::assertSame('database', $result->name);
        self::assertTrue($result->healthy);
        self::assertNull($result->detail);
    }

    public function testUnreachableDatabaseIsUnhealthyAndLogged(): void
    {
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'path' => '/nonexistent/dir/health.db']);

        $logger = new class extends AbstractLogger {
            /** @var list<string> */
            public array $levels = [];

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $this->levels[] = (string) $level;
            }
        };

        $result = (new DatabaseHealthIndicator($connection, $logger))->check();

        self::assertFalse($result->healthy);
        self::assertSame('connection failed', $result->detail);
        self::assertSame(['error'], $logger->levels);
    }
}
