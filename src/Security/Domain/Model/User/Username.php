<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\User;

final readonly class Username implements \Stringable
{
    public const string PATTERN = '/^[^<>]*$/';

    public function __construct(public string $value)
    {
        if (1 !== preg_match(self::PATTERN, $value)) {
            throw new InvalidUsernameException();
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
