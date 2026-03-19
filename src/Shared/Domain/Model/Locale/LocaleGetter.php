<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\Locale;

interface LocaleGetter
{
    public function locale(): ?Locale;

    public function default(): Locale;
}
