<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\Branding;

final readonly class FontFamilyName implements \Stringable
{
    private const string PATTERN = '/^[A-Za-z0-9][A-Za-z0-9 \-]{0,99}$/';

    public function __construct(public string $value)
    {
        if (1 !== preg_match(self::PATTERN, $value)) {
            throw new InvalidFontFamilyNameException($value);
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
