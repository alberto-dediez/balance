<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate;

use App\Shared\Domain\Model\Locale\Locale;

class PropertyCarbonFootprintCertificateFile
{
    public function __construct(
        private PropertyCarbonFootprintCertificateFileId $id,
        private PropertyCarbonFootprintCertificate $certificate,
        private Locale $locale,
        private string $path,
    ) {}

    public function id(): PropertyCarbonFootprintCertificateFileId
    {
        return $this->id;
    }

    public function certificate(): PropertyCarbonFootprintCertificate
    {
        return $this->certificate;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function path(): string
    {
        return $this->path;
    }
}
