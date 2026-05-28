<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\SignatureRequest;

use App\Shared\Domain\Uuid\UuidGenerator;

class Token implements \Stringable
{
    public function __construct(
        private readonly string $hash,
        #[\SensitiveParameter]
        private readonly string $plainValue,
    ) {}

    public static function generate(): self
    {
        $plainValue = UuidGenerator::v4();
        $hash = hash('sha256', $plainValue);

        return new self(hash: $hash, plainValue: $plainValue);
    }

    public function hash(): string
    {
        return $this->hash;
    }

    public function plainValue(): string
    {
        return $this->plainValue;
    }

    public function __toString(): string
    {
        return $this->hash;
    }
}
