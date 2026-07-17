<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EmployeeContribution;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class EmployeeContributionIgnored extends DomainEvent
{
    protected static string $eventName = 'employeeContributionIgnored';

    public function __construct(
        private EmployeeContributionId $employeeContributionId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function employeeContributionId(): EmployeeContributionId
    {
        return $this->employeeContributionId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->employeeContributionId();
    }

    public function payload(): array
    {
        return [
            'employeeContributionId' => (string) $this->employeeContributionId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            employeeContributionId: new EmployeeContributionId($payload['employeeContributionId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
