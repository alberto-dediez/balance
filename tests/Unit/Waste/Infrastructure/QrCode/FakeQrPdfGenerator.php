<?php

namespace App\Tests\Unit\Waste\Infrastructure\QrCode;

use App\Waste\Domain\Model\Container\QrCode;
use App\Waste\Domain\Model\Container\Container;
use App\Waste\Domain\Model\Container\QrPdfGenerator;

class FakeQrPdfGenerator implements QrPdfGenerator
{
    public function generate(Container $container, QrCode $qrCode): string
    {
        return 'path-to-pdf';
    }
}