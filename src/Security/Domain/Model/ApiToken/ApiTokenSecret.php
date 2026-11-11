<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\ApiToken;

final class ApiTokenSecret
{
    public const PREFIX = 'hyd_';
    public const VISIBLE_PREFIX_LENGTH = 12;

    private function __construct(
        #[\SensitiveParameter]
        private readonly string $plainValue,
    ) {}

    public static function generate(): self
    {
        return new self(self::PREFIX . rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='));
    }

    public static function fromPlainValue(#[\SensitiveParameter] string $plainValue): self
    {
        return new self($plainValue);
    }

    public static function hashFor(#[\SensitiveParameter] string $plainValue): string
    {
        return hash('sha256', $plainValue);
    }

    public function plainValue(): string
    {
        return $this->plainValue;
    }

    public function hash(): string
    {
        return self::hashFor($this->plainValue);
    }

    public function prefix(): string
    {
        return substr($this->plainValue, 0, self::VISIBLE_PREFIX_LENGTH);
    }
}
