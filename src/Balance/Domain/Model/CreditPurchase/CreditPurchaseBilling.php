<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\CreditPurchase;

final class CreditPurchaseBilling
{
    private readonly string $legalName;

    private readonly string $taxId;

    private readonly string $street;

    private readonly string $postalCode;

    private readonly string $city;

    private readonly string $country;

    private readonly string $email;

    public function __construct(
        string $legalName,
        string $taxId,
        string $street,
        string $postalCode,
        string $city,
        string $country,
        string $email,
    ) {
        $this->legalName = self::required($legalName, 'legal name');
        $this->taxId = self::required($taxId, 'tax id');
        $this->street = self::required($street, 'street');
        $this->postalCode = self::required($postalCode, 'postal code');
        $this->city = self::required($city, 'city');
        $this->country = strtoupper(self::required($country, 'country'));
        $this->email = self::required($email, 'email');

        if (false === filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Billing email is not valid.');
        }
    }

    public function legalName(): string
    {
        return $this->legalName;
    }

    public function taxId(): string
    {
        return $this->taxId;
    }

    public function street(): string
    {
        return $this->street;
    }

    public function postalCode(): string
    {
        return $this->postalCode;
    }

    public function city(): string
    {
        return $this->city;
    }

    public function country(): string
    {
        return $this->country;
    }

    public function email(): string
    {
        return $this->email;
    }

    private static function required(string $value, string $field): string
    {
        $trimmed = trim($value);

        if ('' === $trimmed) {
            throw new \InvalidArgumentException(sprintf('Billing %s cannot be empty.', $field));
        }

        return $trimmed;
    }
}
