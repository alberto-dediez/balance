<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model;

class Address implements \Stringable
{
    public function __construct(
        private readonly ?string $street = null,
        private readonly ?string $postalCode = null,
        private readonly ?string $city = null,
        private readonly ?string $country = null,
    ) {}

    public function street(): ?string
    {
        return $this->street;
    }

    public function postalCode(): ?string
    {
        return $this->postalCode;
    }

    public function city(): ?string
    {
        return $this->city;
    }

    public function country(): ?string
    {
        return $this->country;
    }

    public function __toString(): string
    {
        return implode(', ', array_filter([
            $this->street,
            $this->postalCode,
            $this->city,
            $this->country,
        ]));
    }
}
