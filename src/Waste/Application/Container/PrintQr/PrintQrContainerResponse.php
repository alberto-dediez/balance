<?php

declare(strict_types=1);

namespace App\Waste\Application\Container\PrintQr;

class PrintQrContainerResponse
{
    public function __construct(public string $path) {}
}
