<?php

declare(strict_types=1);

namespace App\Property\Domain\Model\Property;

use App\Shared\Domain\Model\Locale\Locale;

final class OperatorContact implements \Stringable
{
    private const int MAX_NAME_LENGTH = 255;

    public function __construct(
        private readonly ?string $email = null,
        private readonly ?string $name = null,
        private readonly ?Locale $locale = null,
    ) {}

    public static function from(?string $email, ?string $name, ?Locale $locale): self
    {
        $email = self::normalize($email);
        $name = self::normalize($name);

        if (null !== $email && false === filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidOperatorEmailException($email);
        }

        if (null !== $name && mb_strlen($name) > self::MAX_NAME_LENGTH) {
            throw new \InvalidArgumentException(sprintf('The operator contact name cannot exceed %d characters', self::MAX_NAME_LENGTH));
        }

        return new self(email: $email, name: $name, locale: $locale);
    }

    public function email(): ?string
    {
        return $this->email;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function locale(): ?Locale
    {
        return $this->locale;
    }

    public function isEmpty(): bool
    {
        return null === $this->email && null === $this->name && !$this->locale instanceof Locale;
    }

    public function equals(self $other): bool
    {
        return $this->email === $other->email
            && $this->name === $other->name
            && $this->locale === $other->locale;
    }

    public function __toString(): string
    {
        return $this->email ?? '';
    }

    private static function normalize(?string $value): ?string
    {
        $value = null !== $value ? trim($value) : null;

        return '' === $value ? null : $value;
    }
}
