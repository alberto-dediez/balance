<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\Health;

final readonly class HealthReport
{
    /** @param list<HealthIndicatorResult> $results */
    public function __construct(
        private array $results,
    ) {}

    public function isHealthy(): bool
    {
        foreach ($this->results as $result) {
            if (!$result->healthy) {
                return false;
            }
        }

        return true;
    }

    /** @return list<HealthIndicatorResult> */
    public function results(): array
    {
        return $this->results;
    }

    /** @return array{status: string, checks: array<string, string>} */
    public function toArray(): array
    {
        $checks = [];

        foreach ($this->results as $result) {
            $checks[$result->name] = $result->healthy ? 'ok' : 'error';
        }

        return [
            'status' => $this->isHealthy() ? 'ok' : 'error',
            'checks' => $checks,
        ];
    }
}
