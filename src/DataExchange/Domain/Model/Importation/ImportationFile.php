<?php

namespace App\DataExchange\Domain\Model\Importation;

class ImportationFile
{
    public function __construct(
        private string $path,
        private int $offset = 0
    ) {

    }

    public function path(): string
    {
        return $this->path;
    }

    public function offset(): int
    {
        return $this->offset;
    }
}
