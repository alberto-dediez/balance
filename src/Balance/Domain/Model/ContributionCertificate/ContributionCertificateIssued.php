<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ContributionCertificate;

use App\Balance\Domain\DomainEvent;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Locale\Locale;

final class ContributionCertificateIssued extends DomainEvent
{
    protected static string $eventName = 'contributionCertificateIssued';

    /** @param list<string> $masterSerials */
    public function __construct(
        private ContributionCertificateId $contributionCertificateId,
        private ?AccountId $accountId,
        private ?ProjectContributionId $projectContributionId,
        private string $code,
        private string $verificationToken,
        private ContributionCertificateType $type,
        private Locale $locale,
        private string $beneficiaryName,
        private bool $beneficiaryIsCompany,
        private ?string $beneficiaryVatNumber,
        private string $contactName,
        private string $contactEmail,
        private string $contributorCountry,
        private ?float $co2eqKg,
        private float $tonnes,
        private float $contributionAmount,
        private string $currency,
        private ?ProjectId $projectId,
        private string $projectName,
        private ?string $projectLocation,
        private ?string $projectRegistryId,
        private array $masterSerials,
        private ?ProjectContributionOrigin $origin = null,
        private ?string $originName = null,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function contributionCertificateId(): ContributionCertificateId
    {
        return $this->contributionCertificateId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function projectContributionId(): ?ProjectContributionId
    {
        return $this->projectContributionId;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function verificationToken(): string
    {
        return $this->verificationToken;
    }

    public function type(): ContributionCertificateType
    {
        return $this->type;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function beneficiaryName(): string
    {
        return $this->beneficiaryName;
    }

    public function beneficiaryIsCompany(): bool
    {
        return $this->beneficiaryIsCompany;
    }

    public function beneficiaryVatNumber(): ?string
    {
        return $this->beneficiaryVatNumber;
    }

    public function contactName(): string
    {
        return $this->contactName;
    }

    public function contactEmail(): string
    {
        return $this->contactEmail;
    }

    public function contributorCountry(): string
    {
        return $this->contributorCountry;
    }

    public function co2eqKg(): ?float
    {
        return $this->co2eqKg;
    }

    public function tonnes(): float
    {
        return $this->tonnes;
    }

    public function contributionAmount(): float
    {
        return $this->contributionAmount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function projectId(): ?ProjectId
    {
        return $this->projectId;
    }

    public function projectName(): string
    {
        return $this->projectName;
    }

    public function projectLocation(): ?string
    {
        return $this->projectLocation;
    }

    public function projectRegistryId(): ?string
    {
        return $this->projectRegistryId;
    }

    public function origin(): ?ProjectContributionOrigin
    {
        return $this->origin;
    }

    public function originName(): ?string
    {
        return $this->originName;
    }

    /** @return list<string> */
    public function masterSerials(): array
    {
        return $this->masterSerials;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->contributionCertificateId();
    }

    public function payload(): array
    {
        return [
            'contributionCertificateId' => (string) $this->contributionCertificateId,
            'accountId' => null !== $this->accountId ? (string) $this->accountId : null,
            'projectContributionId' => null !== $this->projectContributionId ? (string) $this->projectContributionId : null,
            'code' => $this->code,
            'verificationToken' => $this->verificationToken,
            'type' => $this->type->value,
            'locale' => $this->locale->value,
            'beneficiaryName' => $this->beneficiaryName,
            'beneficiaryIsCompany' => $this->beneficiaryIsCompany,
            'beneficiaryVatNumber' => $this->beneficiaryVatNumber,
            'contactName' => $this->contactName,
            'contactEmail' => $this->contactEmail,
            'contributorCountry' => $this->contributorCountry,
            'co2eqKg' => $this->co2eqKg,
            'tonnes' => $this->tonnes,
            'contributionAmount' => $this->contributionAmount,
            'currency' => $this->currency,
            'projectId' => null !== $this->projectId ? (string) $this->projectId : null,
            'projectName' => $this->projectName,
            'projectLocation' => $this->projectLocation,
            'projectRegistryId' => $this->projectRegistryId,
            'masterSerials' => $this->masterSerials,
            'origin' => $this->origin?->value,
            'originName' => $this->originName,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        /** @var list<string> $masterSerials */
        $masterSerials = $payload['masterSerials'] ?? [];

        return new ContributionCertificateIssued(
            contributionCertificateId: new ContributionCertificateId($payload['contributionCertificateId']),
            accountId: null !== $payload['accountId'] ? new AccountId($payload['accountId']) : null,
            projectContributionId: null !== $payload['projectContributionId'] ? new ProjectContributionId($payload['projectContributionId']) : null,
            code: (string) $payload['code'],
            verificationToken: (string) ($payload['verificationToken'] ?? ''),
            type: ContributionCertificateType::from((string) $payload['type']),
            locale: Locale::from((string) $payload['locale']),
            beneficiaryName: (string) $payload['beneficiaryName'],
            beneficiaryIsCompany: (bool) $payload['beneficiaryIsCompany'],
            beneficiaryVatNumber: null !== ($payload['beneficiaryVatNumber'] ?? null) ? (string) $payload['beneficiaryVatNumber'] : null,
            contactName: (string) $payload['contactName'],
            contactEmail: (string) $payload['contactEmail'],
            contributorCountry: (string) $payload['contributorCountry'],
            co2eqKg: null !== ($payload['co2eqKg'] ?? null) ? (float) $payload['co2eqKg'] : null,
            tonnes: (float) $payload['tonnes'],
            contributionAmount: (float) $payload['contributionAmount'],
            currency: (string) $payload['currency'],
            projectId: null !== $payload['projectId'] ? new ProjectId($payload['projectId']) : null,
            projectName: (string) $payload['projectName'],
            projectLocation: null !== ($payload['projectLocation'] ?? null) ? (string) $payload['projectLocation'] : null,
            projectRegistryId: null !== ($payload['projectRegistryId'] ?? null) ? (string) $payload['projectRegistryId'] : null,
            masterSerials: $masterSerials,
            origin: null !== ($payload['origin'] ?? null) ? ProjectContributionOrigin::from((string) $payload['origin']) : null,
            originName: null !== ($payload['originName'] ?? null) ? (string) $payload['originName'] : null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
