<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\Branding;

enum PdfFontFamily: string
{
    case Rubik = 'Rubik';
    case Inter = 'Inter';

    public static function default(): self
    {
        return self::Rubik;
    }
}
