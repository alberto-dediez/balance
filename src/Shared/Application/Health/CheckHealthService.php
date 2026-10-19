<?php

declare(strict_types=1);

namespace App\Shared\Application\Health;

use App\Shared\Domain\Model\Health\HealthIndicator;
use App\Shared\Domain\Model\Health\HealthIndicatorResult;
use App\Shared\Domain\Model\Health\HealthReport;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class CheckHealthService
{
    /** @param iterable<HealthIndicator> $indicators */
    public function __construct(
        #[AutowireIterator('shared.health_indicator')]
        private readonly iterable $indicators,
    ) {}

    public function __invoke(): HealthReport
    {
        $results = [];

        foreach ($this->indicators as $indicator) {
            try {
                $results[] = $indicator->check();
            } catch (\Throwable) {
                $results[] = HealthIndicatorResult::unhealthy($indicator->name(), 'check failed');
            }
        }

        return new HealthReport($results);
    }
}
