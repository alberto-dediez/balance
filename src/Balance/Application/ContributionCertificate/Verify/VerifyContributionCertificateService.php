<?php

declare(strict_types=1);

namespace App\Balance\Application\ContributionCertificate\Verify;

use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateRepository;

final class VerifyContributionCertificateService
{
    public function __construct(
        private ContributionCertificateRepository $contributionCertificateRepository,
    ) {}

    public function __invoke(VerifyContributionCertificateRequest $request): ?VerifyContributionCertificateResponse
    {
        $verificationToken = trim($request->verificationToken);

        if ('' === $verificationToken) {
            return null;
        }

        $contributionCertificate = $this->contributionCertificateRepository->findByVerificationToken($verificationToken);

        if (null === $contributionCertificate) {
            return null;
        }

        return new VerifyContributionCertificateResponse(
            code: $contributionCertificate->code(),
            type: $contributionCertificate->type(),
            beneficiaryName: $contributionCertificate->beneficiaryName(),
            projectName: $contributionCertificate->projectName(),
            projectLocation: $contributionCertificate->projectLocation(),
            projectRegistryId: $contributionCertificate->projectRegistryId(),
            co2eqKg: $contributionCertificate->isCompensation() ? $contributionCertificate->co2eqKg() : null,
            masterSerials: $contributionCertificate->masterSerials(),
            creditLots: $contributionCertificate->creditLots(),
            issuedAt: $contributionCertificate->issuedAt(),
            valid: $contributionCertificate->isGenerated(),
        );
    }
}
