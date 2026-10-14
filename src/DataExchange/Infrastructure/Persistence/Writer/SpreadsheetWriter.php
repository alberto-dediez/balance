<?php

declare(strict_types=1);

namespace App\DataExchange\Infrastructure\Persistence\Writer;

use App\DataExchange\Domain\Model\Exportation\ExportationFormat;
use App\DataExchange\Domain\Model\Exportation\ExportWriter;
use App\DataExchange\Domain\Model\Exportation\NotExportedException;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\AbstractWriter;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

class SpreadsheetWriter implements ExportWriter
{
    private ?AbstractWriter $writer = null;

    public function open(string $path, ExportationFormat $format, ?string $title = null): void
    {
        $writer = match ($format) {
            ExportationFormat::Xlsx => new XlsxWriter(),
            ExportationFormat::Csv => new CsvWriter(),
            ExportationFormat::Pdf => throw new NotExportedException('PDF format is not supported by the spreadsheet writer'),
        };

        $writer->openToFile($path);

        $this->writer = $writer;
    }

    public function writeRow(array $cells): void
    {
        if (!$this->writer instanceof AbstractWriter) {
            throw new NotExportedException('Writer has not been opened');
        }

        $this->writer->addRow(Row::fromValues(array_values($cells)));
    }

    public function close(): void
    {
        $this->writer?->close();
        $this->writer = null;
    }
}
