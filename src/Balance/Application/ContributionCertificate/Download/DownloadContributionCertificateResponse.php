<?php

declare(strict_types=1);

namespace App\Balance\Application\ContributionCertificate\Download;

final class DownloadContributionCertificateResponse
{
    public function __construct(
        public readonly mixed $stream,
        public readonly string $filename,
        public readonly string $mimeType = 'application/pdf',
    ) {}
}
