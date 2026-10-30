<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificate;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryPropertyCarbonFootprintCertificateRepository implements PropertyCarbonFootprintCertificateRepository
{
    /** @param array<string, PropertyCarbonFootprintCertificate> $certificates */
    public function __construct(
        private array $certificates = [],
    ) {}

    public function add(PropertyCarbonFootprintCertificate $certificate): void
    {
        $this->certificates[(string) $certificate->id()] = $certificate;
    }

    public function remove(PropertyCarbonFootprintCertificate $certificate): void
    {
        unset($this->certificates[(string) $certificate->id()]);
    }

    public function find(PropertyCarbonFootprintCertificateId $id): ?PropertyCarbonFootprintCertificate
    {
        return $this->certificates[(string) $id] ?? null;
    }

    public function isRevoked(PropertyCarbonFootprintCertificateId $id): bool
    {
        return $this->find($id)?->isRevoked() ?? false;
    }

    public function findByCode(string $code): ?PropertyCarbonFootprintCertificate
    {
        foreach ($this->certificates as $certificate) {
            if ($certificate->code() === $code) {
                return $certificate;
            }
        }

        return null;
    }

    public function findByVerificationToken(string $verificationToken): ?PropertyCarbonFootprintCertificate
    {
        foreach ($this->certificates as $certificate) {
            if ($certificate->verificationToken() === $verificationToken) {
                return $certificate;
            }
        }

        return null;
    }

    public function findCurrentByPropertyCarbonFootprintId(PropertyCarbonFootprintId $id): ?PropertyCarbonFootprintCertificate
    {
        foreach ($this->findByPropertyCarbonFootprintId($id) as $certificate) {
            if (!$certificate->isRevoked()) {
                return $certificate;
            }
        }

        return null;
    }

    public function findByPropertyCarbonFootprintId(PropertyCarbonFootprintId $id): array
    {
        $certificates = array_values(array_filter(
            $this->certificates,
            static fn(PropertyCarbonFootprintCertificate $certificate): bool => $certificate->propertyCarbonFootprintId()?->equals($id) ?? false,
        ));

        usort(
            $certificates,
            static fn(PropertyCarbonFootprintCertificate $a, PropertyCarbonFootprintCertificate $b): int => $b->issuedAt() <=> $a->issuedAt(),
        );

        return $certificates;
    }
}
