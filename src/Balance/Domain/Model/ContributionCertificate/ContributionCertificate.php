<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ContributionCertificate;

use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Locale\Locale;

/**
 * Immutable proof of a contribution: the figures are snapshotted when it is issued, so the document
 * keeps saying the same thing however the project or its prices change afterwards.
 */
class ContributionCertificate implements \Stringable
{
    use AggregateRoot;

    /**
     * @param list<string>          $masterSerials
     * @param array<string, string> $files
     * @param array<mixed>          $events
     */
    public function __construct(
        private ContributionCertificateId $id,
        private ?AccountId $accountId = null,
        private ?ProjectContributionId $projectContributionId = null,
        private string $code = '',
        private string $verificationToken = '',
        private ContributionCertificateType $type = ContributionCertificateType::SocialContribution,
        private ContributionCertificateStatus $status = ContributionCertificateStatus::Pending,
        private Locale $locale = Locale::Spanish,
        private string $beneficiaryName = '',
        private bool $beneficiaryIsCompany = false,
        private ?string $beneficiaryVatNumber = null,
        private string $contactName = '',
        private string $contactEmail = '',
        private string $contributorCountry = '',
        private ?float $co2eqKg = null,
        private float $tonnes = 0.0,
        private float $contributionAmount = 0.0,
        private string $currency = 'EUR',
        private ?ProjectId $projectId = null,
        private string $projectName = '',
        private ?string $projectLocation = null,
        private ?string $projectRegistryId = null,
        private array $masterSerials = [],
        private array $files = [],
        private ?string $failureReason = null,
        array $events = [],
        private \DateTimeImmutable $issuedAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $generatedAt = null,
        private ?\DateTimeImmutable $failedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): ContributionCertificateId
    {
        return $this->id;
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

    /**
     * The secret the QR carries. It is never printed as text: whoever scans the document reaches the
     * verification page holding it, and the page answers with the reference to compare against paper.
     */
    public function verificationToken(): string
    {
        return $this->verificationToken;
    }

    public function type(): ContributionCertificateType
    {
        return $this->type;
    }

    public function status(): ContributionCertificateStatus
    {
        return $this->status;
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

    /** @return list<string> */
    public function masterSerials(): array
    {
        return $this->masterSerials;
    }

    /** @return array<string, string> */
    public function files(): array
    {
        return $this->files;
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

    public function isCompensation(): bool
    {
        return $this->type->isCompensation();
    }

    public function isGenerated(): bool
    {
        return $this->status->isGenerated();
    }

    public function fileFor(Locale $locale): ?string
    {
        return $this->files[$locale->value] ?? $this->files[$this->locale->value] ?? null;
    }

    /** @param list<string> $masterSerials */
    public function issue(
        AccountId $accountId,
        ProjectContributionId $projectContributionId,
        string $code,
        string $verificationToken,
        ContributionCertificateType $type,
        Locale $locale,
        string $beneficiaryName,
        bool $beneficiaryIsCompany,
        ?string $beneficiaryVatNumber,
        string $contactName,
        string $contactEmail,
        string $contributorCountry,
        ?float $co2eqKg,
        float $tonnes,
        float $contributionAmount,
        string $currency,
        ?ProjectId $projectId,
        string $projectName,
        ?string $projectLocation,
        ?string $projectRegistryId,
        array $masterSerials,
    ): void {
        if ('' === $code) {
            throw new \InvalidArgumentException('Contribution certificate code cannot be empty.');
        }

        if ('' === $verificationToken) {
            throw new \InvalidArgumentException('Contribution certificate verification token cannot be empty.');
        }

        $this->recordApplyAndPublish(
            new ContributionCertificateIssued(
                contributionCertificateId: $this->id(),
                accountId: $accountId,
                projectContributionId: $projectContributionId,
                code: $code,
                verificationToken: $verificationToken,
                type: $type,
                locale: $locale,
                beneficiaryName: $beneficiaryName,
                beneficiaryIsCompany: $beneficiaryIsCompany,
                beneficiaryVatNumber: $beneficiaryVatNumber,
                contactName: $contactName,
                contactEmail: $contactEmail,
                contributorCountry: $contributorCountry,
                // A social contribution never states a volume of emissions, so the figure is not
                // even stored: nothing downstream can print what is not there.
                co2eqKg: $type->isCompensation() ? $co2eqKg : null,
                tonnes: $tonnes,
                contributionAmount: $contributionAmount,
                currency: $currency,
                projectId: $projectId,
                projectName: $projectName,
                projectLocation: $projectLocation,
                projectRegistryId: $type->isCompensation() ? $projectRegistryId : null,
                masterSerials: $type->isCompensation() ? $masterSerials : [],
            ),
        );
    }

    /** @param array<string, string> $files */
    public function markGenerated(array $files): void
    {
        $this->recordApplyAndPublish(
            new ContributionCertificateGenerated(
                contributionCertificateId: $this->id(),
                files: $files,
            ),
        );
    }

    public function markFailed(string $reason): void
    {
        $this->recordApplyAndPublish(
            new ContributionCertificateFailed(
                contributionCertificateId: $this->id(),
                reason: $reason,
            ),
        );
    }

    public function markSent(string $email): void
    {
        $this->recordApplyAndPublish(
            new ContributionCertificateSent(
                contributionCertificateId: $this->id(),
                email: $email,
            ),
        );
    }

    public function applyContributionCertificateIssued(ContributionCertificateIssued $event): void
    {
        $this->accountId = $event->accountId();
        $this->projectContributionId = $event->projectContributionId();
        $this->code = $event->code();
        $this->verificationToken = $event->verificationToken();
        $this->type = $event->type();
        $this->locale = $event->locale();
        $this->beneficiaryName = $event->beneficiaryName();
        $this->beneficiaryIsCompany = $event->beneficiaryIsCompany();
        $this->beneficiaryVatNumber = $event->beneficiaryVatNumber();
        $this->contactName = $event->contactName();
        $this->contactEmail = $event->contactEmail();
        $this->contributorCountry = $event->contributorCountry();
        $this->co2eqKg = $event->co2eqKg();
        $this->tonnes = $event->tonnes();
        $this->contributionAmount = $event->contributionAmount();
        $this->currency = $event->currency();
        $this->projectId = $event->projectId();
        $this->projectName = $event->projectName();
        $this->projectLocation = $event->projectLocation();
        $this->projectRegistryId = $event->projectRegistryId();
        $this->masterSerials = $event->masterSerials();
        $this->status = ContributionCertificateStatus::Pending;
        $this->issuedAt = $event->occurredOn();
    }

    public function applyContributionCertificateGenerated(ContributionCertificateGenerated $event): void
    {
        $this->files = $event->files();
        $this->status = ContributionCertificateStatus::Generated;
        $this->failureReason = null;
        $this->failedAt = null;
        $this->generatedAt = $event->occurredOn();
    }

    public function applyContributionCertificateFailed(ContributionCertificateFailed $event): void
    {
        $this->status = ContributionCertificateStatus::Failed;
        $this->failureReason = $event->reason();
        $this->failedAt = $event->occurredOn();
    }

    /** Deliveries do not change the document, they are only recorded for the audit trail. */
    public function applyContributionCertificateSent(ContributionCertificateSent $event): void {}

    public function __toString(): string
    {
        return $this->code;
    }
}
