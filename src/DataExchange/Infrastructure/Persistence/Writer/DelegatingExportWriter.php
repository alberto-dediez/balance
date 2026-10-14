<?php

declare(strict_types=1);

namespace App\DataExchange\Infrastructure\Persistence\Writer;

use App\DataExchange\Domain\Model\Exportation\ExportationFormat;
use App\DataExchange\Domain\Model\Exportation\ExportWriter;
use App\DataExchange\Domain\Model\Exportation\NotExportedException;

class DelegatingExportWriter implements ExportWriter
{
    private ?ExportWriter $writer = null;

    public function __construct(
        private readonly SpreadsheetWriter $spreadsheetWriter,
        private readonly TcpdfExportWriter $tcpdfExportWriter,
    ) {}

    public function open(string $path, ExportationFormat $format, ?string $title = null): void
    {
        $writer = match ($format) {
            ExportationFormat::Xlsx, ExportationFormat::Csv => $this->spreadsheetWriter,
            ExportationFormat::Pdf => $this->tcpdfExportWriter,
        };

        $writer->open($path, $format, $title);

        $this->writer = $writer;
    }

    public function writeRow(array $cells): void
    {
        if (!$this->writer instanceof ExportWriter) {
            throw new NotExportedException('Writer has not been opened');
        }

        $this->writer->writeRow($cells);
    }

    public function close(): void
    {
        $this->writer?->close();
        $this->writer = null;
    }
}
