<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Infrastructure\QrCode;

use App\Waste\Domain\Model\Container\QrPdfMerger;

class FakeQrPdfMerger implements QrPdfMerger
{
    public function merge(string $pathToPdf, string $pathToOtherPdf): string
    {
        return 'path-to-pdf';
    }
}
