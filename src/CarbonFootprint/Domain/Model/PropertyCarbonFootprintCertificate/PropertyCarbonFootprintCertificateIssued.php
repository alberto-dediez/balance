<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Locale\Locale;

final class PropertyCarbonFootprintCertificateIssued extends DomainEvent
{
    protected static string $eventName = 'propertyCarbonFootprintCertificateIssued';

    public function __construct(
        private PropertyCarbonFootprintCertificateId $propertyCarbonFootprintCertificateId,
        private PropertyCarbonFootprintId $propertyCarbonFootprintId,
        private PropertyId $propertyId,
        private AccountId $accountId,
        private int $year,
        private string $code,
        private string $verificationToken,
        private Locale $locale,
        private string $propertyName,
        private string $accountName,
        private int $stays,
        private float $totalCo2eqKg,
        private float $co2eqKgPerStay,
        private float $scope1Co2eqKg,
        private float $scope2Co2eqKg,
        private ?float $scope3Co2eqKg,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function propertyCarbonFootprintCertificateId(): PropertyCarbonFootprintCertificateId
    {
        return $this->propertyCarbonFootprintCertificateId;
    }

    public function propertyCarbonFootprintId(): PropertyCarbonFootprintId
    {
        return $this->propertyCarbonFootprintId;
    }

    public function propertyId(): PropertyId
    {
        return $this->propertyId;
    }

    public function accountId(): AccountId
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

    public function aggregateRootId(): AggregateRootId
    {
        return $this->propertyCarbonFootprintCertificateId;
    }

    public function payload(): array
    {
        return [
            'propertyCarbonFootprintCertificateId' => (string) $this->propertyCarbonFootprintCertificateId,
            'propertyCarbonFootprintId' => (string) $this->propertyCarbonFootprintId,
            'propertyId' => (string) $this->propertyId,
            'accountId' => (string) $this->accountId,
            'year' => $this->year,
            'code' => $this->code,
            'verificationToken' => $this->verificationToken,
            'locale' => $this->locale->value,
            'propertyName' => $this->propertyName,
            'accountName' => $this->accountName,
            'stays' => $this->stays,
            'totalCo2eqKg' => $this->totalCo2eqKg,
            'co2eqKgPerStay' => $this->co2eqKgPerStay,
            'scope1Co2eqKg' => $this->scope1Co2eqKg,
            'scope2Co2eqKg' => $this->scope2Co2eqKg,
            'scope3Co2eqKg' => $this->scope3Co2eqKg,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            propertyCarbonFootprintCertificateId: new PropertyCarbonFootprintCertificateId($payload['propertyCarbonFootprintCertificateId']),
            propertyCarbonFootprintId: new PropertyCarbonFootprintId($payload['propertyCarbonFootprintId']),
            propertyId: new PropertyId($payload['propertyId']),
            accountId: new AccountId($payload['accountId']),
            year: (int) $payload['year'],
            code: (string) $payload['code'],
            verificationToken: (string) $payload['verificationToken'],
            locale: Locale::from((string) $payload['locale']),
            propertyName: (string) $payload['propertyName'],
            accountName: (string) $payload['accountName'],
            stays: (int) $payload['stays'],
            totalCo2eqKg: (float) $payload['totalCo2eqKg'],
            co2eqKgPerStay: (float) $payload['co2eqKgPerStay'],
            scope1Co2eqKg: (float) $payload['scope1Co2eqKg'],
            scope2Co2eqKg: (float) $payload['scope2Co2eqKg'],
            scope3Co2eqKg: null !== ($payload['scope3Co2eqKg'] ?? null) ? (float) $payload['scope3Co2eqKg'] : null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
