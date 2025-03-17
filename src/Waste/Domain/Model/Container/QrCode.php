<?php

namespace App\Waste\Domain\Model\Container;

class QrCode
{
    public function __construct(
        private string $path
    ) {
        
    }

    public function path(): string
    {
        return $this->path;
    }
}