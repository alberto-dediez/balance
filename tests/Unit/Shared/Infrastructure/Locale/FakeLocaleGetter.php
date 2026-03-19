<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Locale;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\LocaleGetter;

readonly class FakeLocaleGetter implements LocaleGetter
{
    public function __construct(
        private Locale $locale = Locale::Spanish,
        private Locale $default = Locale::Spanish,
    ) {

    }

    public function locale(): ?Locale
    {
        return $this->locale;
    }

    public function default(): Locale
    {
        return $this->default;
    }
}
