<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\Locale;

interface Translation
{
    public function locale(): Locale;

    public function translatable(): Translatable;

    public function update(Translation $translation): void;
}
