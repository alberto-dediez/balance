<?php

namespace App\Shared\Domain\Uuid;

use App\Shared\Domain\Uuid\UuidGenerator;

trait UuidTrait
{
    public function __construct(private string $id)
    {

    }

    public static function generate(): self
    {
        $id = UuidGenerator::v4();

        return new self($id);
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