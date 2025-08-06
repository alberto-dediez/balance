<?php

namespace App\Shared\Domain\Model\Locale;

enum Locale: string
{
    case Spanish = 'es';

    case English = 'en';

    case French = 'fr';

    case Italian = 'it';

    case Turkish = 'tr';

    case German = 'de';
}