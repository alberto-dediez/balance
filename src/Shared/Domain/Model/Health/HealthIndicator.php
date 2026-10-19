<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\Health;

interface HealthIndicator
{
    public function name(): string;

    public function check(): HealthIndicatorResult;
}
