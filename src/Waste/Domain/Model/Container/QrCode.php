<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\Container;

class QrCode
{
    public function __construct(
        private readonly string $path,
    ) {}

    public function path(): string
    {
        return $this->path;
    }
}
