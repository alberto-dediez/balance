<?php

declare(strict_types=1);

namespace App\DataExchange\Infrastructure\Persistence\PDO\Tools;

use App\DataExchange\Domain\Model\Importation\ImportationFile;
use App\DataExchange\Domain\Model\Importation\NotImportedException;
use OpenSpout\Reader\XLSX\Reader;

class FileExtractor
{
    private readonly iterable $iterator;

    private array $columns = [];

    public function __construct(private readonly ImportationFile $importationFile)
    {
        $reader = match ($this->importationFile->extension()) {
            'xlsx', 'xls' => new Reader(),
            'csv' => new \OpenSpout\Reader\CSV\Reader(),
            'ods' => new \OpenSpout\Reader\ODS\Reader(),
            default => throw new NotImportedException('Invalid file extension'),
        };

        $reader->open($this->importationFile->path());

        $sheet = $reader->getSheetIterator()->current();

        $this->iterator = $sheet->getRowIterator();
        $this->iterator->rewind();

        $offset = $this->importationFile->offset();

        while ($offset > 0) {
            --$offset;
            $this->iterator->next();
        }

        foreach ($this->iterator->current()->getCells() as $cell) {
            $this->columns[] = $cell->getValue();
        }

        $this->columns = ColumnSanitizer::sanitize($this->columns);
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getRow(): ?array
    {
        $this->iterator->next();

        if (!$this->iterator->valid()) {
            return null;
        }

        $row = array_fill_keys($this->columns, null);

        foreach ($this->iterator->current()->getCells() as $index0 => $cell) {
            if (!isset($this->columns[$index0])) {
                break;
            }

            $columnName = $this->columns[$index0];
            $row[$columnName] = $cell->getValue();
        }

        return $row;
    }
}
