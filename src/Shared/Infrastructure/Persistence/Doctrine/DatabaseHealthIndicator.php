<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine;

use App\Shared\Domain\Model\Health\HealthIndicator;
use App\Shared\Domain\Model\Health\HealthIndicatorResult;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class DatabaseHealthIndicator implements HealthIndicator
{
    public function __construct(
        private readonly Connection $connection,
        private readonly LoggerInterface $logger,
    ) {}

    public function name(): string
    {
        return 'database';
    }

    public function check(): HealthIndicatorResult
    {
        try {
            $this->connection->executeQuery('SELECT 1')->fetchOne();

            return HealthIndicatorResult::healthy($this->name());
        } catch (\Throwable $e) {
            $this->logger->error('Database health check failed', ['exception' => $e]);

            return HealthIndicatorResult::unhealthy($this->name(), 'connection failed');
        }
    }
}
