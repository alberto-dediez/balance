<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OperatorContribution;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Locale\Locale;

final class OperatorContributionRegistered extends DomainEvent
{
    protected static string $eventName = 'operatorContributionRegistered';

    public function __construct(
        private OperatorContributionId $contributionId,
        private CompanyCarbonFootprintId $companyCarbonFootprintId,
        private AccountId $accountId,
        private PropertyId $propertyId,
        private Source $source,
        private int $year,
        private ?EmissionFactorGroupId $emissionFactorGroupId,
        private string $contactEmail,
        private ?string $contactName,
        private Locale $contactLocale,
        #[\SensitiveParameter]
        private string $token,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function contributionId(): OperatorContributionId
    {
        return $this->contributionId;
    }

    public function companyCarbonFootprintId(): CompanyCarbonFootprintId
    {
        return $this->companyCarbonFootprintId;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function propertyId(): PropertyId
    {
        return $this->propertyId;
    }

    public function source(): Source
    {
        return $this->source;
    }

    public function year(): int
    {
        return $this->year;
    }

    public function emissionFactorGroupId(): ?EmissionFactorGroupId
    {
        return $this->emissionFactorGroupId;
    }

    public function contactEmail(): string
    {
        return $this->contactEmail;
    }

    public function contactName(): ?string
    {
        return $this->contactName;
    }

    public function contactLocale(): Locale
    {
        return $this->contactLocale;
    }

    public function token(): string
    {
        return $this->token;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->contributionId();
    }

    public function payload(): array
    {
        return [
            'contributionId' => (string) $this->contributionId,
            'companyCarbonFootprintId' => (string) $this->companyCarbonFootprintId,
            'accountId' => (string) $this->accountId,
            'propertyId' => (string) $this->propertyId,
            'source' => $this->source->value,
            'year' => $this->year,
            'emissionFactorGroupId' => $this->emissionFactorGroupId instanceof EmissionFactorGroupId ? (string) $this->emissionFactorGroupId : null,
            'contactEmail' => $this->contactEmail,
            'contactName' => $this->contactName,
            'contactLocale' => $this->contactLocale->value,
            'token' => $this->token,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $source = Source::tryFrom($payload['source']);
        assert(null !== $source);

        $contactLocale = Locale::tryFrom($payload['contactLocale']);
        assert(null !== $contactLocale);

        return new self(
            contributionId: new OperatorContributionId($payload['contributionId']),
            companyCarbonFootprintId: new CompanyCarbonFootprintId($payload['companyCarbonFootprintId']),
            accountId: new AccountId($payload['accountId']),
            propertyId: new PropertyId($payload['propertyId']),
            source: $source,
            year: (int) $payload['year'],
            emissionFactorGroupId: isset($payload['emissionFactorGroupId']) ? new EmissionFactorGroupId($payload['emissionFactorGroupId']) : null,
            contactEmail: $payload['contactEmail'],
            contactName: $payload['contactName'] ?? null,
            contactLocale: $contactLocale,
            token: $payload['token'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
