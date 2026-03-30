<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\Locale;

class LocaleGetter
{
    private static ?Locale $locale = null;
    private static Locale $default = Locale::Spanish;

    public static function setLocale(?Locale $locale): void
    {
        self::$locale = $locale;
    }

    public static function setDefault(Locale $default): void
    {
        self::$default = $default;
    }

    public static function locale(): Locale
    {
        return self::$locale ?? self::default();
    }

    public static function default(): Locale
    {
        return self::$default;
    }
}
