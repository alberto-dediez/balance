<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Integration;

final readonly class IntegrationSettings
{
    public const string PLATFORM_PATTERN = '/^[a-z0-9-]+$/';

    public function __construct(
        public bool $enabled,
        public string $platform,
        public int $maxHops,
    ) {
        if (1 !== preg_match(self::PLATFORM_PATTERN, $platform)) {
            throw new \InvalidArgumentException(sprintf('Invalid integration platform "%s": only lowercase letters, digits and hyphens are allowed', $platform));
        }
    }
}
