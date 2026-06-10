<?php

declare(strict_types=1);

namespace App\DataExchange\Domain\Model\Exportation;

enum ExportationFormat: string
{
    case Xlsx = 'xlsx';

    case Csv = 'csv';

    public function extension(): string
    {
        return $this->value;
    }

    public function mimeType(): string
    {
        return match ($this) {
            self::Xlsx => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            self::Csv => 'text/csv',
        };
    }
}
