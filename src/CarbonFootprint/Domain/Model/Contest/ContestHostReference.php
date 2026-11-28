<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Contest;

final class ContestHostReference
{
    public function __construct(
        public readonly ContestHostType $hostType,
        public readonly string $hostId,
    ) {
        if ('' === trim($this->hostId)) {
            throw new \InvalidArgumentException('Contest host id cannot be empty.');
        }
    }

    public static function fromKey(string $key): self
    {
        [$type, $id] = array_pad(explode(':', $key, 2), 2, '');

        return new self(ContestHostType::from($type), $id);
    }

    public function key(): string
    {
        return $this->hostType->value . ':' . $this->hostId;
    }

    public function equals(self $other): bool
    {
        return $this->hostType === $other->hostType && $this->hostId === $other->hostId;
    }
}
