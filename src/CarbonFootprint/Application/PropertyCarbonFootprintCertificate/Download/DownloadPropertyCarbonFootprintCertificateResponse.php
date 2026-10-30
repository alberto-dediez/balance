<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Download;

final class DownloadPropertyCarbonFootprintCertificateResponse
{
    public function __construct(
        public readonly mixed $stream,
        public readonly string $filename,
        public readonly string $mimeType = 'application/pdf',
    ) {}
}
