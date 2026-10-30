<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Generate;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateData;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificatePdfGenerator;
use App\Shared\Domain\Model\Branding\PdfBranding;
use App\Shared\Domain\Model\Locale\Locale;

class FakePropertyCarbonFootprintCertificatePdfGenerator implements PropertyCarbonFootprintCertificatePdfGenerator
{
    /** @var list<PropertyCarbonFootprintCertificateData> */
    private array $rendered = [];

    public function __construct(
        private readonly ?Locale $failOn = null,
        private readonly ?\Closure $onGenerate = null,
    ) {}

    public function generate(PropertyCarbonFootprintCertificateData $data, Locale $locale, PdfBranding $branding): string
    {
        if ($this->failOn === $locale) {
            throw new \RuntimeException('rendering failure');
        }

        $this->rendered[] = $data;

        if (null !== $this->onGenerate) {
            ($this->onGenerate)();
        }

        $path = sprintf('%s/%s.pdf', sys_get_temp_dir(), uniqid('fake-footprint-certificate-', true));
        file_put_contents($path, 'pdf-' . $locale->value);

        return $path;
    }

    /** @return list<PropertyCarbonFootprintCertificateData> */
    public function rendered(): array
    {
        return $this->rendered;
    }
}
