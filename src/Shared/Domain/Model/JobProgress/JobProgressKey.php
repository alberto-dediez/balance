<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\JobProgress;

class JobProgressKey implements \Stringable
{
    public function __construct(private readonly string $value) {}

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
