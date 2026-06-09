<?php

declare(strict_types=1);

namespace App\DataExchange\Infrastructure\UI\Web\DTO;

use App\DataExchange\Domain\Model\Exportation\ExportationFormat;

class ExportOptionsRequest
{
    /** @param string[] $columns */
    public function __construct(
        public array $columns = [],
        public ExportationFormat $format = ExportationFormat::Xlsx,
    ) {}
}
