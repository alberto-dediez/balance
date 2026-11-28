<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ContestEntry;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\Contest\ContestHostType;
use App\CarbonFootprint\Domain\Model\Contest\ContestId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Consent\ConsentProof;
use App\Shared\Domain\Model\Locale\Locale;

final class ContestEntryRegistered extends DomainEvent
{
    protected static string $eventName = 'contestEntryRegistered';

    public function __construct(
        private readonly ContestEntryId $contestEntryId,
        private readonly ContestId $contestId,
        private readonly AccountId $accountId,
        private readonly ContestHostType $hostType,
        private readonly string $hostId,
        private readonly ProjectContributionOrigin $origin,
        private readonly string $originId,
        private readonly ?string $projectContributionId,
        private readonly string $name,
        private readonly string $email,
        private readonly ?string $phone,
        private readonly string $country,
        private readonly Locale $locale,
        private readonly ConsentProof $consentProof,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function contestEntryId(): ContestEntryId
    {
        return $this->contestEntryId;
    }

    public function contestId(): ContestId
    {
        return $this->contestId;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function hostType(): ContestHostType
    {
        return $this->hostType;
    }

    public function hostId(): string
    {
        return $this->hostId;
    }

    public function origin(): ProjectContributionOrigin
    {
        return $this->origin;
    }

    public function originId(): string
    {
        return $this->originId;
    }

    public function projectContributionId(): ?string
    {
        return $this->projectContributionId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function phone(): ?string
    {
        return $this->phone;
    }

    public function country(): string
    {
        return $this->country;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function consentProof(): ConsentProof
    {
        return $this->consentProof;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->contestEntryId;
    }

    public function payload(): array
    {
        return [
            'contestEntryId' => (string) $this->contestEntryId,
            'contestId' => (string) $this->contestId,
            'accountId' => (string) $this->accountId,
            'hostType' => $this->hostType->value,
            'hostId' => $this->hostId,
            'origin' => $this->origin->value,
            'originId' => $this->originId,
            'projectContributionId' => $this->projectContributionId,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'country' => $this->country,
            'locale' => $this->locale->value,
            'consentProofConsentedAt' => $this->consentProof->consentedAt()?->format(\DateTimeInterface::ATOM),
            'consentProofPolicyVersion' => $this->consentProof->policyVersion(),
            'consentProofLocale' => $this->consentProof->locale(),
            'consentProofIp' => $this->consentProof->ip(),
            'consentProofUserAgent' => $this->consentProof->userAgent(),
            'consentProofAcceptLanguage' => $this->consentProof->acceptLanguage(),
        ];
    }

    public static function rebuildFromPayload(DomainEventId $id, \DateTimeImmutable $occurredOn, array $payload = []): static
    {
        return new self(
            contestEntryId: new ContestEntryId($payload['contestEntryId']),
            contestId: new ContestId($payload['contestId']),
            accountId: new AccountId($payload['accountId']),
            hostType: ContestHostType::from($payload['hostType']),
            hostId: $payload['hostId'],
            origin: ProjectContributionOrigin::from($payload['origin']),
            originId: $payload['originId'],
            projectContributionId: $payload['projectContributionId'],
            name: $payload['name'],
            email: $payload['email'],
            phone: $payload['phone'],
            country: $payload['country'],
            locale: Locale::from($payload['locale']),
            consentProof: new ConsentProof(
                consentedAt: new \DateTimeImmutable((string) $payload['consentProofConsentedAt']),
                policyVersion: $payload['consentProofPolicyVersion'],
                locale: $payload['consentProofLocale'],
                ip: $payload['consentProofIp'],
                userAgent: $payload['consentProofUserAgent'],
                acceptLanguage: $payload['consentProofAcceptLanguage'],
            ),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
