<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\ContributionCertificate\Generate;

use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateData;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificatePdfGenerator;
use App\Shared\Domain\Model\Locale\Locale;

class FakeContributionCertificatePdfGenerator implements ContributionCertificatePdfGenerator
{
    /** @var list<ContributionCertificateData> */
    private array $rendered = [];

    public function __construct(private readonly ?Locale $failOn = null) {}

    public function generate(ContributionCertificateData $data, Locale $locale): string
    {
        if ($this->failOn === $locale) {
            throw new \RuntimeException('rendering failure');
        }

        $this->rendered[] = $data;

        $path = sprintf('%s/%s.pdf', sys_get_temp_dir(), uniqid('fake-certificate-', true));
        file_put_contents($path, 'pdf-' . $locale->value);

        return $path;
    }

    /** @return list<ContributionCertificateData> */
    public function rendered(): array
    {
        return $this->rendered;
    }
}
