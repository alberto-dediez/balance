<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Certificate;

use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateVerificationUrlGenerator;
use App\Shared\Domain\Model\Locale\Locale;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class RouterContributionCertificateVerificationUrlGenerator implements ContributionCertificateVerificationUrlGenerator
{
    public function __construct(private UrlGeneratorInterface $urlGenerator) {}

    public function generate(string $verificationToken, Locale $locale): string
    {
        return $this->urlGenerator->generate(
            'public_contribution_certificate_verify',
            ['token' => $verificationToken, '_locale' => $locale->value],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
    }
}
