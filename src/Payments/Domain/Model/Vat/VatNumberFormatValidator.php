<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Vat;

/**
 * Validates the *format* of an intra-community VAT number per member state.
 * There is no VIES lookup: a well-formed number is accepted as valid.
 *
 * The country prefix is optional, so a guest may type their national number as they know it.
 */
final class VatNumberFormatValidator
{
    /** Member states whose VAT prefix is not their ISO code. */
    private const PREFIXES = [
        'GR' => 'EL',
    ];

    /** @var array<string, string> */
    private const PATTERNS = [
        'AT' => '/^U\d{8}$/',
        'BE' => '/^0\d{9}$/',
        'BG' => '/^\d{9,10}$/',
        'CY' => '/^\d{8}[A-Z]$/',
        'CZ' => '/^\d{8,10}$/',
        'DE' => '/^\d{9}$/',
        'DK' => '/^\d{8}$/',
        'EE' => '/^\d{9}$/',
        'ES' => '/^[A-Z0-9]\d{7}[A-Z0-9]$/',
        'FI' => '/^\d{8}$/',
        'FR' => '/^[A-Z0-9]{2}\d{9}$/',
        'GR' => '/^\d{9}$/',
        'HR' => '/^\d{11}$/',
        'HU' => '/^\d{8}$/',
        'IE' => '/^(\d{7}[A-Z]{1,2}|\d[A-Z0-9+*]\d{5}[A-Z])$/',
        'IT' => '/^\d{11}$/',
        'LT' => '/^(\d{9}|\d{12})$/',
        'LU' => '/^\d{8}$/',
        'LV' => '/^\d{11}$/',
        'MT' => '/^\d{8}$/',
        'NL' => '/^\d{9}B\d{2}$/',
        'PL' => '/^\d{10}$/',
        'PT' => '/^\d{9}$/',
        'RO' => '/^\d{2,10}$/',
        'SE' => '/^\d{12}$/',
        'SI' => '/^\d{8}$/',
        'SK' => '/^\d{10}$/',
    ];

    public function isValid(?string $vatNumber, string $countryCode): bool
    {
        if (null === $vatNumber || '' === trim($vatNumber)) {
            return false;
        }

        $normalized = $this->normalize($vatNumber);
        $country = strtoupper(trim($countryCode));

        if (!isset(self::PATTERNS[$country])) {
            return false;
        }

        return 1 === preg_match(self::PATTERNS[$country], $this->withoutPrefix($normalized, $country));
    }

    /** Greek numbers carry the EL prefix, but the ISO code is accepted too because guests do type it. */
    private function withoutPrefix(string $normalized, string $country): string
    {
        foreach ([self::PREFIXES[$country] ?? $country, $country] as $prefix) {
            if (str_starts_with($normalized, $prefix)) {
                return substr($normalized, strlen($prefix));
            }
        }

        return $normalized;
    }

    public function normalize(string $vatNumber): string
    {
        return strtoupper((string) preg_replace('/[\s\-\.]/', '', $vatNumber));
    }
}
