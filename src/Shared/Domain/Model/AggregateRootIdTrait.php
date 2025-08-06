<?php

namespace App\Shared\Domain\Model;

use Symfony\Component\Uid\Uuid;

trait AggregateRootIdTrait
{
    public function __construct(private string $id)
    {

    }

    public static function fromString(string $id): AggregateRootId
    {
        return new self($id);
    }

    public static function generate(): AggregateRootId
    {
        $id = Uuid::v4();

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