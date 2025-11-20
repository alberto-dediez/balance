<?php

namespace App\DataExchange\Infrastructure\Persistence\PDO\Tools;

use App\DataExchange\Domain\Model\Importation\ImportationFile;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class FileExtractor
{
    private iterable $iterator;

    private array $columns = [];

    public function __construct(private ImportationFile $importationFile)
    {
        $spreadsheet = IOFactory::load($this->importationFile->path());

        $sheet = $spreadsheet->getActiveSheet();

        $this->iterator = $sheet->getRowIterator();

        $offset = $this->importationFile->offset();

        while ($offset > 0) {
            $offset--;
            $this->iterator->next();
        }

        foreach ($this->iterator->current()->getCellIterator() as $cell) {
            $this->columns[] = $cell->getCalculatedValue();
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

        foreach ($this->iterator->current()->getCellIterator() as $cell) {
            $index0 = Coordinate::columnIndexFromString($cell->getColumn()) - 1;

            if (!isset($this->columns[$index0])) {
                break;
            }

            $columnName = $this->columns[$index0];
            $row[$columnName] = $cell->getCalculatedValue();
        }

        return $row;
    }
}
