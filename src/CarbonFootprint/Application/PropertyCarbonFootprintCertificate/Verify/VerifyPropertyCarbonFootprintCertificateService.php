<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Verify;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateRepository;

final class VerifyPropertyCarbonFootprintCertificateService
{
    public function __construct(
        private PropertyCarbonFootprintCertificateRepository $certificateRepository,
    ) {}

    public function __invoke(VerifyPropertyCarbonFootprintCertificateRequest $request): ?VerifyPropertyCarbonFootprintCertificateResponse
    {
        $verificationToken = trim($request->verificationToken);

        if ('' === $verificationToken) {
            return null;
        }

        $certificate = $this->certificateRepository->findByVerificationToken($verificationToken);

        if (null === $certificate) {
            return null;
        }

        return new VerifyPropertyCarbonFootprintCertificateResponse(
            code: $certificate->code(),
            propertyName: $certificate->propertyName(),
            accountName: $certificate->accountName(),
            year: $certificate->year(),
            stays: $certificate->stays(),
            co2eqKgPerStay: $certificate->co2eqKgPerStay(),
            totalCo2eqKg: $certificate->totalCo2eqKg(),
            scope1Co2eqKg: $certificate->scope1Co2eqKg(),
            scope2Co2eqKg: $certificate->scope2Co2eqKg(),
            scope3Co2eqKg: $certificate->scope3Co2eqKg(),
            issuedAt: $certificate->issuedAt(),
            revokedAt: $certificate->revokedAt(),
            status: $certificate->status(),
            valid: $certificate->isValid(),
            accountId: $certificate->accountId(),
        );
    }
}
