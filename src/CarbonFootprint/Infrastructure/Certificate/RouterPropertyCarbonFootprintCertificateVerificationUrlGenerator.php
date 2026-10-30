<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Certificate;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateVerificationUrlGenerator;
use App\Shared\Domain\Model\Locale\Locale;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class RouterPropertyCarbonFootprintCertificateVerificationUrlGenerator implements PropertyCarbonFootprintCertificateVerificationUrlGenerator
{
    public function __construct(private UrlGeneratorInterface $urlGenerator) {}

    public function generate(string $verificationToken, Locale $locale): string
    {
        return $this->urlGenerator->generate(
            'public_property_carbon_footprint_certificate_verify',
            ['token' => $verificationToken, '_locale' => $locale->value],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
    }
}
