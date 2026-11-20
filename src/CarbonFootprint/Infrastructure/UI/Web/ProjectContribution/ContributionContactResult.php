<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\ProjectContribution;

final readonly class ContributionContactResult
{
    /** @param array<string, mixed> $parameters */
    private function __construct(
        public ?string $redirectUrl = null,
        public array $parameters = [],
    ) {}

    public static function redirect(string $redirectUrl): self
    {
        return new self(redirectUrl: $redirectUrl);
    }

    /** @param array<string, mixed> $parameters */
    public static function render(array $parameters): self
    {
        return new self(parameters: $parameters);
    }
}
