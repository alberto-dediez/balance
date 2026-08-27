<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Vat;

final class VatNumberFormatValidator
{
    /** @var array<string, string> */
    private const PREFIXES = [
        'AUT' => 'AT',
        'BEL' => 'BE',
        'BGR' => 'BG',
        'CYP' => 'CY',
        'CZE' => 'CZ',
        'DEU' => 'DE',
        'DNK' => 'DK',
        'ESP' => 'ES',
        'EST' => 'EE',
        'FIN' => 'FI',
        'FRA' => 'FR',
        'GRC' => 'EL',
        'HRV' => 'HR',
        'HUN' => 'HU',
        'IRL' => 'IE',
        'ITA' => 'IT',
        'LTU' => 'LT',
        'LUX' => 'LU',
        'LVA' => 'LV',
        'MLT' => 'MT',
        'NLD' => 'NL',
        'POL' => 'PL',
        'PRT' => 'PT',
        'ROU' => 'RO',
        'SVK' => 'SK',
        'SVN' => 'SI',
        'SWE' => 'SE',
    ];

    /** @var array<string, string> */
    private const PATTERNS = [
        'AUT' => '/^U\d{8}$/',
        'BEL' => '/^0\d{9}$/',
        'BGR' => '/^\d{9,10}$/',
        'CYP' => '/^\d{8}[A-Z]$/',
        'CZE' => '/^\d{8,10}$/',
        'DEU' => '/^\d{9}$/',
        'DNK' => '/^\d{8}$/',
        'ESP' => '/^[A-Z0-9]\d{7}[A-Z0-9]$/',
        'EST' => '/^\d{9}$/',
        'FIN' => '/^\d{8}$/',
        'FRA' => '/^[A-Z0-9]{2}\d{9}$/',
        'GRC' => '/^\d{9}$/',
        'HRV' => '/^\d{11}$/',
        'HUN' => '/^\d{8}$/',
        'IRL' => '/^(\d{7}[A-Z]{1,2}|\d[A-Z0-9+*]\d{5}[A-Z])$/',
        'ITA' => '/^\d{11}$/',
        'LTU' => '/^(\d{9}|\d{12})$/',
        'LUX' => '/^\d{8}$/',
        'LVA' => '/^\d{11}$/',
        'MLT' => '/^\d{8}$/',
        'NLD' => '/^\d{9}B\d{2}$/',
        'POL' => '/^\d{10}$/',
        'PRT' => '/^\d{9}$/',
        'ROU' => '/^\d{2,10}$/',
        'SVK' => '/^\d{10}$/',
        'SVN' => '/^\d{8}$/',
        'SWE' => '/^\d{12}$/',
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
