<?php

declare(strict_types=1);

namespace App\Shared\Domain\Uuid;

trait UuidTrait
{
    public function __construct(private string $id) {}

    public static function generate(): static
    {
        $id = UuidGenerator::v7();

        return new static($id);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function equals(self $anotherId): bool
    {
        return $this->id() === $anotherId->id();
    }

    public function __toString(): string
    {
        return $this->id();
    }
}
