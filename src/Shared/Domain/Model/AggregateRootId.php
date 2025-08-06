<?php

namespace App\Shared\Domain\Model;

interface AggregateRootId
{
    public function id(): string;

    public static function generate(): self;

    public function equals(self $anotherId): bool;

    public function __toString(): string;

    public static function fromString(string $id): self;
}