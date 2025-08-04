<?php

namespace App\Waste\Domain\Model\Container;

interface QrPdfMerger
{
    public function merge(string $pathToPdf, string $otherPathToPdf): string;
}