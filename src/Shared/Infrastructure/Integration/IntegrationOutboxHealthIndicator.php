<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Integration;

use App\Shared\Domain\Model\Health\HealthIndicator;
use App\Shared\Domain\Model\Health\HealthIndicatorResult;
use App\Shared\Domain\Model\Integration\IntegrationOutboxRepository;

class IntegrationOutboxHealthIndicator implements HealthIndicator
{
    public function __construct(
        private readonly IntegrationSettings $settings,
        private readonly IntegrationOutboxRepository $outbox,
        private readonly int $maxWaitMinutes,
    ) {}

    public function name(): string
    {
        return 'integration_outbox';
    }

    public function check(): HealthIndicatorResult
    {
        if (!$this->settings->enabled) {
            return HealthIndicatorResult::healthy($this->name());
        }

        $oldest = $this->outbox->oldestPendingOccurredOn();

        if (!$oldest instanceof \DateTimeImmutable) {
            return HealthIndicatorResult::healthy($this->name());
        }

        $limit = (new \DateTimeImmutable('NOW', new \DateTimeZone('UTC')))->modify(sprintf('-%d minutes', $this->maxWaitMinutes));

        if ($oldest < $limit) {
            return HealthIndicatorResult::unhealthy($this->name(), 'outbox messages waiting too long');
        }

        return HealthIndicatorResult::healthy($this->name());
    }
}
