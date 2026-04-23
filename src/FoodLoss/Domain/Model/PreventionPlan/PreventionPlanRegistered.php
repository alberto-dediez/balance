<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\PreventionPlan;

use App\FoodLoss\Domain\DomainEvent;
use App\Property\Domain\Model\Company\CompanyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class PreventionPlanRegistered extends DomainEvent
{
    protected static string $eventName = 'preventionPlanRegistered';

    public function __construct(
        private PreventionPlanId $preventionPlanId,
        private string $name,
        private CompanyId $companyId,
        private AccountId $accountId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function preventionPlanId(): PreventionPlanId
    {
        return $this->preventionPlanId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function companyId(): CompanyId
    {
        return $this->companyId;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->preventionPlanId();
    }

    public function payload(): array
    {
        return [
            'preventionPlanId' => (string) $this->preventionPlanId,
            'name' => $this->name,
            'companyId' => (string) $this->companyId,
            'accountId' => (string) $this->accountId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new PreventionPlanRegistered(
            preventionPlanId: new PreventionPlanId($payload['preventionPlanId']),
            name: $payload['name'],
            companyId: !empty($payload['companyId']) ? new CompanyId($payload['companyId']) : CompanyId::generate(),
            accountId: new AccountId($payload['accountId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
