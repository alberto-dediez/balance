<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Health;

use App\Shared\Domain\Model\Health\HealthIndicator;
use App\Shared\Domain\Model\Health\HealthIndicatorResult;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryHealthIndicator implements HealthIndicator
{
    private ?string $failure = null;

    public function name(): string
    {
        return 'in-memory';
    }

    public function markUnhealthy(string $detail = 'forced failure'): void
    {
        $this->failure = $detail;
    }

    public function markHealthy(): void
    {
        $this->failure = null;
    }

    public function check(): HealthIndicatorResult
    {
        if (null === $this->failure) {
            return HealthIndicatorResult::healthy($this->name());
        }

        return HealthIndicatorResult::unhealthy($this->name(), $this->failure);
    }
}
