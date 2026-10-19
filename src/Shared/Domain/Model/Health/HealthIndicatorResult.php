<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\Health;

final readonly class HealthIndicatorResult
{
    public function __construct(
        public string $name,
        public bool $healthy,
        public ?string $detail = null,
    ) {}

    public static function healthy(string $name): self
    {
        return new self($name, true);
    }

    public static function unhealthy(string $name, string $detail): self
    {
        return new self($name, false, $detail);
    }
}
