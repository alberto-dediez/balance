<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\ContributionCertificate\Generate;

use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateData;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificatePdfGenerator;
use App\Shared\Domain\Model\Branding\PdfBranding;
use App\Shared\Domain\Model\Locale\Locale;

class FakeContributionCertificatePdfGenerator implements ContributionCertificatePdfGenerator
{
    /** @var list<ContributionCertificateData> */
    private array $rendered = [];

    /** @var list<PdfBranding> */
    private array $brandings = [];

    public function __construct(private readonly ?Locale $failOn = null) {}

    public function generate(ContributionCertificateData $data, Locale $locale, PdfBranding $branding): string
    {
        if ($this->failOn === $locale) {
            throw new \RuntimeException('rendering failure');
        }

        $this->rendered[] = $data;
        $this->brandings[] = $branding;

        $path = sprintf('%s/%s.pdf', sys_get_temp_dir(), uniqid('fake-certificate-', true));
        file_put_contents($path, 'pdf-' . $locale->value);

        return $path;
    }

    /** @return list<ContributionCertificateData> */
    public function rendered(): array
    {
        return $this->rendered;
    }

    /** @return list<PdfBranding> */
    public function brandings(): array
    {
        return $this->brandings;
    }
}
