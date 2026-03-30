<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model;

use App\Shared\Domain\Uuid\UuidGenerator;

trait AggregateRootIdTrait
{
    public function __construct(private string $id) {}

    public static function fromString(string $id): self
    {
        return new self($id);
    }

    public static function generate(): self
    {
        $id = UuidGenerator::v7();

        return new self($id);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function equals(AggregateRootId $anotherId): bool
    {
        return $this->id() === $anotherId->id();
    }

    public function __toString(): string
    {
        return $this->id();
    }
}
