<?php

declare(strict_types=1);

namespace App\Shared\Application\Search;

final readonly class SearchTerm implements \Stringable
{
    public const int MIN_LENGTH = 2;

    public string $value;

    public function __construct(string $value)
    {
        $this->value = trim($value);
    }

    public function isSearchable(): bool
    {
        return mb_strlen($this->value) >= self::MIN_LENGTH;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
