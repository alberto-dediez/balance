<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Tcpdf;

final readonly class PdfKeyFigure
{
    public function __construct(
        public string $label,
        public string $value,
        public string $unit = '',
    ) {}
}
