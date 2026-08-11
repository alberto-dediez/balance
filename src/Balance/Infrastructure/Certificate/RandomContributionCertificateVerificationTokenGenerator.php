<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Certificate;

use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateRepository;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateVerificationTokenGenerator;

/**
 * The token travels only inside the QR, so it is drawn long and URL safe: holding the certificate,
 * or even its printed reference, must not be enough to reach anybody else's verification page.
 */
final class RandomContributionCertificateVerificationTokenGenerator implements ContributionCertificateVerificationTokenGenerator
{
    private const int BYTES = 32;

    private const int MAX_ATTEMPTS = 10;

    public function __construct(private ContributionCertificateRepository $contributionCertificateRepository) {}

    public function generate(): string
    {
        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; ++$attempt) {
            $verificationToken = rtrim(strtr(base64_encode(random_bytes(self::BYTES)), '+/', '-_'), '=');

            if (null === $this->contributionCertificateRepository->findByVerificationToken($verificationToken)) {
                return $verificationToken;
            }
        }

        throw new \RuntimeException('Unable to generate a unique contribution certificate verification token.');
    }
}
