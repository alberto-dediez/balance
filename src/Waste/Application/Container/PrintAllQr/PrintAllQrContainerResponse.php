<?php

declare(strict_types=1);

namespace App\Waste\Application\Container\PrintAllQr;

class PrintAllQrContainerResponse
{
    public function __construct(public string $path) {}
}
