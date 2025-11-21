<?php

namespace App\DataExchange\Infrastructure\Persistence\PDO\Tools;

use App\DataExchange\Domain\Model\Importation\ImportationFile;
use App\DataExchange\Domain\Model\Importation\NotImportedException;
use OpenSpout\Reader\Common\Creator\ReaderFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class FileExtractor
{
    private iterable $iterator;

    private array $columns = [];

    public function __construct(private ImportationFile $importationFile)
    {
        switch ($this->importationFile->extension()) {
            case 'xlsx':
            case 'xls':
                $reader = new \OpenSpout\Reader\XLSX\Reader();
                break;
            case 'csv':
                $reader = new \OpenSpout\Reader\CSV\Reader();
                break;
            case 'ods':
                $reader = new \OpenSpout\Reader\ODS\Reader();
                break;
            default:
                throw new NotImportedException('Invalid file extension');
        }

        $reader->open($this->importationFile->path());

        $sheet = $reader->getSheetIterator()->current();

        $this->iterator = $sheet->getRowIterator();
        $this->iterator->rewind();

        $offset = $this->importationFile->offset();

        while ($offset > 0) {
            $offset--;
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
