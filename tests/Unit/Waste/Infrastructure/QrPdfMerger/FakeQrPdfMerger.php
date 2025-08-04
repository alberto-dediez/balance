<?php

namespace App\Tests\Unit\Waste\Infrastructure\QrPdfMerger;

use App\Waste\Domain\Model\Container\QrPdfMerger;

class FakeQrPdfMerger implements QrPdfMerger
{
    public function merge(string $pathToPdf, string $pathToOtherPdf): string
    {
        return 'path-to-pdf';
    }
}