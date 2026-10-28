<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class OrganizationalProviderContributionRegistered extends DomainEvent
{
    protected static string $eventName = 'organizationalProviderContributionRegistered';

    /** @param list<Source> $sources */
    public function __construct(
        private OrganizationalProviderContributionId $contributionId,
        private CarbonFootprintReportSubjectType $subjectType,
        private string $carbonFootprintId,
        private AccountId $accountId,
        private ProviderId $providerId,
        private ?PropertyId $propertyId,
        private ProviderContributionMode $mode,
        #[\SensitiveParameter]
        private string $token,
        private array $sources,
        private ?string $companyName,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function contributionId(): OrganizationalProviderContributionId
    {
        return $this->contributionId;
    }

    public function subjectType(): CarbonFootprintReportSubjectType
    {
        return $this->subjectType;
    }

    public function carbonFootprintId(): string
    {
        return $this->carbonFootprintId;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function providerId(): ProviderId
    {
        return $this->providerId;
    }

    public function propertyId(): ?PropertyId
    {
        return $this->propertyId;
    }

    public function mode(): ProviderContributionMode
    {
        return $this->mode;
    }

    public function token(): string
    {
        return $this->token;
    }

    /** @return list<Source> */
    public function sources(): array
    {
        return $this->sources;
    }

    public function companyName(): ?string
    {
        return $this->companyName;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->contributionId();
    }

    public function payload(): array
    {
        return [
            'contributionId' => (string) $this->contributionId,
            'subjectType' => $this->subjectType->value,
            'carbonFootprintId' => $this->carbonFootprintId,
            'accountId' => (string) $this->accountId,
            'providerId' => (string) $this->providerId,
            'propertyId' => null !== $this->propertyId ? (string) $this->propertyId : null,
            'mode' => $this->mode->value,
            'token' => $this->token,
            'sources' => array_map(static fn(Source $source): string => $source->value, $this->sources),
            'companyName' => $this->companyName,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            contributionId: new OrganizationalProviderContributionId($payload['contributionId']),
            subjectType: CarbonFootprintReportSubjectType::from($payload['subjectType']),
            carbonFootprintId: $payload['carbonFootprintId'],
            accountId: new AccountId($payload['accountId']),
            providerId: new ProviderId($payload['providerId']),
            propertyId: null !== ($payload['propertyId'] ?? null) ? new PropertyId($payload['propertyId']) : null,
            mode: ProviderContributionMode::from($payload['mode']),
            token: $payload['token'],
            sources: array_values(array_map(static fn(string $source): Source => Source::from($source), $payload['sources'] ?? [])),
            companyName: $payload['companyName'] ?? null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
