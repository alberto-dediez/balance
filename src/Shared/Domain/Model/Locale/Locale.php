<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\Locale;

enum Locale: string
{
    case Spanish = 'es';

    case English = 'en';

    case French = 'fr';

    case Italian = 'it';

    case Turkish = 'tr';

    case German = 'de';

    /** @return list<self> */
    public static function listFrom(string $localeList): array
    {
        return array_values(
            array_filter(
                array_map(static fn(string $code) => self::tryFrom(trim($code)), explode('|', $localeList)),
                static fn(?self $locale) => $locale instanceof self,
            ),
        );
    }
}
