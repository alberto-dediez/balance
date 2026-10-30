<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Certificate;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateRepository;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateVerificationTokenGenerator;

final class RandomPropertyCarbonFootprintCertificateVerificationTokenGenerator implements PropertyCarbonFootprintCertificateVerificationTokenGenerator
{
    private const int BYTES = 32;

    private const int MAX_ATTEMPTS = 10;

    public function __construct(private PropertyCarbonFootprintCertificateRepository $certificateRepository) {}

    public function generate(): string
    {
        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; ++$attempt) {
            $verificationToken = rtrim(strtr(base64_encode(random_bytes(self::BYTES)), '+/', '-_'), '=');

            if (null === $this->certificateRepository->findByVerificationToken($verificationToken)) {
                return $verificationToken;
            }
        }

        throw new \RuntimeException('Unable to generate a unique property carbon footprint certificate verification token.');
    }
}
