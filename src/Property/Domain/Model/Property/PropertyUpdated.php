<?php

declare(strict_types=1);

namespace App\Property\Domain\Model\Property;

use App\Property\Domain\DomainEvent;
use App\Property\Domain\Model\Company\CompanyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Locale\Locale;

final class PropertyUpdated extends DomainEvent
{
    protected static string $eventName = 'propertyUpdated';

    public function __construct(
        private readonly PropertyId $propertyId,
        private readonly string $name,
        private readonly ?PropertyInfo $info = null,
        private readonly ?ExternalId $externalId = null,
        private readonly ?AccountId $accountId = null,
        private readonly ?CompanyId $companyId = null,
        private readonly ?int $numberOfEmployees = null,
        private readonly ?int $dailyClients = null,
        private readonly OperatingRegime $operatingRegime = OperatingRegime::Owned,
        private readonly ?OperatorContact $operatorContact = null,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function propertyId(): PropertyId
    {
        return $this->propertyId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function info(): ?PropertyInfo
    {
        return $this->info;
    }

    public function externalId(): ?ExternalId
    {
        return $this->externalId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function companyId(): ?CompanyId
    {
        return $this->companyId;
    }

    public function numberOfEmployees(): ?int
    {
        return $this->numberOfEmployees;
    }

    public function dailyClients(): ?int
    {
        return $this->dailyClients;
    }

    public function operatingRegime(): OperatingRegime
    {
        return $this->operatingRegime;
    }

    public function operatorContact(): ?OperatorContact
    {
        return $this->operatorContact;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->propertyId;
    }

    public function payload(): array
    {
        return [
            'propertyId' => (string) $this->propertyId,
            'accountId' => $this->accountId instanceof AccountId ? ((string) $this->accountId) : null,
            'companyId' => $this->companyId instanceof CompanyId ? ((string) $this->companyId) : null,
            'name' => $this->name,
            'info' => $this->info,
            'externalId' => $this->externalId,
            'numberOfEmployees' => $this->numberOfEmployees,
            'dailyClients' => $this->dailyClients,
            'operatingRegime' => $this->operatingRegime->value,
            'operatorEmail' => $this->operatorContact?->email(),
            'operatorName' => $this->operatorContact?->name(),
            'operatorLocale' => $this->operatorContact?->locale()?->value,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new PropertyUpdated(
            propertyId: new PropertyId($payload['propertyId']),
            name: $payload['name'],
            info: $payload['info'] ?? null,
            externalId: $payload['externalId'] ?? null,
            accountId: null !== ($payload['accountId'] ?? null) ? new AccountId($payload['accountId']) : null,
            companyId: null !== ($payload['companyId'] ?? null) ? new CompanyId($payload['companyId']) : null,
            numberOfEmployees: $payload['numberOfEmployees'] ?? null,
            dailyClients: $payload['dailyClients'] ?? null,
            operatingRegime: OperatingRegime::tryFrom($payload['operatingRegime'] ?? '') ?? OperatingRegime::Owned,
            operatorContact: OperatorContact::from($payload['operatorEmail'] ?? null, $payload['operatorName'] ?? null, Locale::tryFrom($payload['operatorLocale'] ?? '')),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
