<?php

namespace App\Waste\Domain\Model\Container;

use App\Waste\Domain\Model\Container\QrCode;
use App\Waste\Domain\Model\Container\Container;

interface QrPdfGenerator
{
    /**
     * @return string $path
     */
    public function generate(Container $container, QrCode $qrCode): string;
}