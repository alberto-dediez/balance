<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Qr;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

class QrCodeGenerator
{
    public function generate(string $data): QrCodeImage
    {
        $result = (new Builder(writer: new PngWriter(), data: $data))->build();

        return new QrCodeImage($result->getDataUri());
    }
}
