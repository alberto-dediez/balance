<?php

declare(strict_types=1);

namespace App\Changelog\Domain\Model\ChangelogEntry;

final class ChangelogEntrySlug
{
    private const string PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*\z/';

    public function __construct(private readonly string $value)
    {
        if (1 !== preg_match(self::PATTERN, $this->value)) {
            throw new InvalidChangelogEntryException($this->value, sprintf('slug "%s" does not match "%s"', $this->value, self::PATTERN));
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
