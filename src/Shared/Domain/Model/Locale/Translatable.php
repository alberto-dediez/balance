<?php

namespace App\Shared\Domain\Model\Locale;

interface Translatable
{
    public function id(): mixed;

    public function translations(): Translations;
}