<?php

namespace App\Tests\Unit\Shared\Infrastructure\Locale;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class FakeLocaleGetter implements LocaleGetter
{
    public function __construct(
        private readonly Locale $locale = Locale::Spanish
    ) {

    }

    public function locale(): ?Locale
    {
        return $this->locale;
    }

    public function default(): Locale
    {
        return $this->locale;
    }
}