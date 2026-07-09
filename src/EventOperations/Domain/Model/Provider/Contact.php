<?php

declare(strict_types=1);

namespace App\EventOperations\Domain\Model\Provider;

use App\Shared\Domain\Model\Locale\Locale;

class Contact implements \Stringable
{
    public function __construct(
        private readonly string $email = '',
        private readonly ?Locale $preferredLocale = null,
        private readonly ?string $name = null,
    ) {}

    public static function fromEmail(string $email, ?Locale $preferredLocale = null, ?string $name = null): self
    {
        if ('' === $email || false === filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidProviderEmailException($email);
        }

        return new self(email: $email, preferredLocale: $preferredLocale, name: $name);
    }

    public function email(): string
    {
        return $this->email;
    }

    public function preferredLocale(): ?Locale
    {
        return $this->preferredLocale;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function __toString(): string
    {
        return $this->email;
    }
}
