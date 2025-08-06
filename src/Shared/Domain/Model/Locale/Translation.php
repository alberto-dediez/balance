<?php

namespace App\Shared\Domain\Model\Locale;

use App\Shared\Domain\Model\Locale\Locale;

interface Translation
{
    public function locale(): Locale;

    public function translatableId(): mixed;
}