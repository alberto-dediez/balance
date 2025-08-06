<?php

namespace App\Shared\Domain\Model\Locale;

use App\Shared\Domain\Model\Locale\Locale;

interface LocaleGetter 
{
    public function locale(): ?Locale;
    
    public function default(): Locale;
}