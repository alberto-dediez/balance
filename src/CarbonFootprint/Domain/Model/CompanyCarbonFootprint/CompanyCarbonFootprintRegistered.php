<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Property\Domain\Model\Company\CompanyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CompanyCarbonFootprintRegistered extends DomainEvent
{
    protected static string $eventName = 'companyCarbonFootprintRegistered';

    public function __construct(
        private CompanyCarbonFootprintId $companyCarbonFootprintId,
        private CompanyId $companyId,
        private AccountId $accountId,
        private EmissionFactorGroupId $emissionFactorGroupId,
        private int $year,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function companyCarbonFootprintId(): CompanyCarbonFootprintId
    {
        return $this->companyCarbonFootprintId;
    }

    public function companyId(): CompanyId
    {
        return $this->companyId;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function emissionFactorGroupId(): EmissionFactorGroupId
    {
        return $this->emissionFactorGroupId;
    }

    public function year(): int
    {
        return $this->year;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->companyCarbonFootprintId;
    }

    public function payload(): array
    {
        return [
            'companyCarbonFootprintId' => (string) $this->companyCarbonFootprintId,
            'companyId' => (string) $this->companyId,
            'accountId' => (string) $this->accountId,
            'emissionFactorGroupId' => (string) $this->emissionFactorGroupId,
            'year' => $this->year,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            companyCarbonFootprintId: new CompanyCarbonFootprintId($payload['companyCarbonFootprintId']),
            companyId: new CompanyId($payload['companyId']),
            accountId: new AccountId($payload['accountId']),
            emissionFactorGroupId: new EmissionFactorGroupId($payload['emissionFactorGroupId']),
            year: (int) $payload['year'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
