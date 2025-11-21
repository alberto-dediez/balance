<?php

namespace App\DataExchange\Domain\Model\Importation;

class ImportationFile
{
    public function __construct(
        private string $path,
        private string $extension = '',
        private int $offset = 0
    ) {
        if (empty($this->extension)) {
            $this->extension = pathinfo($this->path, PATHINFO_EXTENSION);
        }
    }

    public function path(): string
    {
        return $this->path;
    }

    public function extension(): string
    {
        return $this->extension;
    }

    public function offset(): int
    {
        return $this->offset;
    }
}
