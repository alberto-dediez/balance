<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Locale;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\LocaleGetter;

readonly class FakeLocaleSetter
{
    public static function set(
        Locale $locale = Locale::Spanish,
        Locale $default = Locale::Spanish,
    ): void {
        LocaleGetter::setLocale($locale);
        LocaleGetter::setDefault($default);
    }

    public static function reset(): void {
        LocaleGetter::setLocale(Locale::Spanish,);
        LocaleGetter::setDefault(Locale::Spanish,);
    }
}
