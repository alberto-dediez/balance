<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Locale\Locale;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class PropertyCarbonFootprintCertificate implements \Stringable
{
    use AggregateRoot;

    /** @var iterable<PropertyCarbonFootprintCertificateFile>|Collection<int, PropertyCarbonFootprintCertificateFile> */
    private iterable $files = [];

    /** @param array<mixed> $events */
    public function __construct(
        private PropertyCarbonFootprintCertificateId $id,
        private ?PropertyCarbonFootprintId $propertyCarbonFootprintId = null,
        private ?PropertyId $propertyId = null,
        private ?AccountId $accountId = null,
        private int $year = 0,
        private string $code = '',
        private string $verificationToken = '',
        private PropertyCarbonFootprintCertificateStatus $status = PropertyCarbonFootprintCertificateStatus::Pending,
        private Locale $locale = Locale::Spanish,
        private string $propertyName = '',
        private string $accountName = '',
        private int $stays = 0,
        private float $totalCo2eqKg = 0.0,
        private float $co2eqKgPerStay = 0.0,
        private float $scope1Co2eqKg = 0.0,
        private float $scope2Co2eqKg = 0.0,
        private ?float $scope3Co2eqKg = null,
        private ?string $failureReason = null,
        array $events = [],
        private \DateTimeImmutable $issuedAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $generatedAt = null,
        private ?\DateTimeImmutable $failedAt = null,
        private ?\DateTimeImmutable $revokedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): PropertyCarbonFootprintCertificateId
    {
        return $this->id;
    }

    public function propertyCarbonFootprintId(): ?PropertyCarbonFootprintId
    {
        return $this->propertyCarbonFootprintId;
    }

    public function propertyId(): ?PropertyId
    {
        return $this->propertyId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function year(): int
    {
        return $this->year;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function verificationToken(): string
    {
        return $this->verificationToken;
    }

    public function status(): PropertyCarbonFootprintCertificateStatus
    {
        return $this->status;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function propertyName(): string
    {
        return $this->propertyName;
    }

    public function accountName(): string
    {
        return $this->accountName;
    }

    public function stays(): int
    {
        return $this->stays;
    }

    public function totalCo2eqKg(): float
    {
        return $this->totalCo2eqKg;
    }

    public function co2eqKgPerStay(): float
    {
        return $this->co2eqKgPerStay;
    }

    public function scope1Co2eqKg(): float
    {
        return $this->scope1Co2eqKg;
    }

    public function scope2Co2eqKg(): float
    {
        return $this->scope2Co2eqKg;
    }

    public function scope3Co2eqKg(): ?float
    {
        return $this->scope3Co2eqKg;
    }

    public function failureReason(): ?string
    {
        return $this->failureReason;
    }

    public function issuedAt(): \DateTimeImmutable
    {
        return $this->issuedAt;
    }

    public function generatedAt(): ?\DateTimeImmutable
    {
        return $this->generatedAt;
    }

    public function failedAt(): ?\DateTimeImmutable
    {
        return $this->failedAt;
    }

    public function revokedAt(): ?\DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function isGenerated(): bool
    {
        return $this->status->isGenerated();
    }

    public function isRevoked(): bool
    {
        return $this->status->isRevoked();
    }

    public function isValid(): bool
    {
        return $this->status->isGenerated();
    }

    /** @return array<string, string> */
    public function files(): array
    {
        $files = [];

        foreach ($this->fileCollection() as $file) {
            $files[$file->locale()->value] = $file->path();
        }

        return $files;
    }

    public function fileFor(Locale $locale): ?string
    {
        $files = $this->files();

        return $files[$locale->value] ?? $files[$this->locale->value] ?? null;
    }

    public function issue(
        PropertyCarbonFootprintId $propertyCarbonFootprintId,
        PropertyId $propertyId,
        AccountId $accountId,
        int $year,
        string $code,
        string $verificationToken,
        Locale $locale,
        string $propertyName,
        string $accountName,
        int $stays,
        float $totalCo2eqKg,
        float $scope1Co2eqKg,
        float $scope2Co2eqKg,
        ?float $scope3Co2eqKg,
    ): void {
        if ('' === $code) {
            throw new \InvalidArgumentException('Property carbon footprint certificate code cannot be empty.');
        }

        if ('' === $verificationToken) {
            throw new \InvalidArgumentException('Property carbon footprint certificate verification token cannot be empty.');
        }

        if ($stays < 1) {
            throw new \InvalidArgumentException('Property carbon footprint certificate stays must be greater than zero.');
        }

        $this->recordApplyAndPublish(
            new PropertyCarbonFootprintCertificateIssued(
                propertyCarbonFootprintCertificateId: $this->id,
                propertyCarbonFootprintId: $propertyCarbonFootprintId,
                propertyId: $propertyId,
                accountId: $accountId,
                year: $year,
                code: $code,
                verificationToken: $verificationToken,
                locale: $locale,
                propertyName: $propertyName,
                accountName: $accountName,
                stays: $stays,
                totalCo2eqKg: $totalCo2eqKg,
                co2eqKgPerStay: round($totalCo2eqKg / $stays, 2),
                scope1Co2eqKg: $scope1Co2eqKg,
                scope2Co2eqKg: $scope2Co2eqKg,
                scope3Co2eqKg: $scope3Co2eqKg,
            ),
        );
    }

    /** @param array<string, string> $files */
    public function markGenerated(array $files): void
    {
        if ($this->status->isRevoked()) {
            return;
        }

        $this->recordApplyAndPublish(
            new PropertyCarbonFootprintCertificateGenerated(
                propertyCarbonFootprintCertificateId: $this->id,
                files: $files,
            ),
        );
    }

    public function markFailed(string $reason): void
    {
        if ($this->status->isRevoked()) {
            return;
        }

        $this->recordApplyAndPublish(
            new PropertyCarbonFootprintCertificateFailed(
                propertyCarbonFootprintCertificateId: $this->id,
                reason: $reason,
            ),
        );
    }

    public function revoke(): void
    {
        if ($this->status->isRevoked()) {
            return;
        }

        $this->recordApplyAndPublish(
            new PropertyCarbonFootprintCertificateRevoked(
                propertyCarbonFootprintCertificateId: $this->id,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new PropertyCarbonFootprintCertificateRemoved(
                propertyCarbonFootprintCertificateId: $this->id,
            ),
        );
    }

    public function applyPropertyCarbonFootprintCertificateIssued(PropertyCarbonFootprintCertificateIssued $event): void
    {
        $this->propertyCarbonFootprintId = $event->propertyCarbonFootprintId();
        $this->propertyId = $event->propertyId();
        $this->accountId = $event->accountId();
        $this->year = $event->year();
        $this->code = $event->code();
        $this->verificationToken = $event->verificationToken();
        $this->locale = $event->locale();
        $this->propertyName = $event->propertyName();
        $this->accountName = $event->accountName();
        $this->stays = $event->stays();
        $this->totalCo2eqKg = $event->totalCo2eqKg();
        $this->co2eqKgPerStay = $event->co2eqKgPerStay();
        $this->scope1Co2eqKg = $event->scope1Co2eqKg();
        $this->scope2Co2eqKg = $event->scope2Co2eqKg();
        $this->scope3Co2eqKg = $event->scope3Co2eqKg();
        $this->status = PropertyCarbonFootprintCertificateStatus::Pending;
        $this->issuedAt = $event->occurredOn();
    }

    public function applyPropertyCarbonFootprintCertificateGenerated(PropertyCarbonFootprintCertificateGenerated $event): void
    {
        $collection = $this->fileCollection();
        $collection->clear();

        foreach ($event->files() as $locale => $path) {
            $collection->add(new PropertyCarbonFootprintCertificateFile(
                id: PropertyCarbonFootprintCertificateFileId::generate(),
                certificate: $this,
                locale: Locale::from($locale),
                path: $path,
            ));
        }

        $this->status = PropertyCarbonFootprintCertificateStatus::Generated;
        $this->failureReason = null;
        $this->failedAt = null;
        $this->generatedAt = $event->occurredOn();
    }

    public function applyPropertyCarbonFootprintCertificateFailed(PropertyCarbonFootprintCertificateFailed $event): void
    {
        $this->status = PropertyCarbonFootprintCertificateStatus::Failed;
        $this->failureReason = $event->reason();
        $this->failedAt = $event->occurredOn();
    }

    public function applyPropertyCarbonFootprintCertificateRevoked(PropertyCarbonFootprintCertificateRevoked $event): void
    {
        $this->status = PropertyCarbonFootprintCertificateStatus::Revoked;
        $this->revokedAt = $event->occurredOn();
    }

    public function applyPropertyCarbonFootprintCertificateRemoved(PropertyCarbonFootprintCertificateRemoved $event): void {}

    public function __toString(): string
    {
        return $this->code;
    }

    /** @return Collection<int, PropertyCarbonFootprintCertificateFile> */
    private function fileCollection(): Collection
    {
        if (!$this->files instanceof Collection) {
            $this->files = new ArrayCollection(
                $this->files instanceof \Traversable ? iterator_to_array($this->files) : $this->files,
            );
        }

        return $this->files;
    }
}
