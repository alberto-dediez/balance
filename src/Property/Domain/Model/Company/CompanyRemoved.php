<?php

declare(strict_types=1);

namespace App\Property\Domain\Model\Company;

use App\Property\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CompanyRemoved extends DomainEvent
{
    protected static string $eventName = 'companyRemoved';

    public function __construct(
        private CompanyId $companyId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function companyId(): CompanyId
    {
        return $this->companyId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->companyId();
    }

    public function payload(): array
    {
        return [
            'companyId' => (string) $this->companyId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new CompanyRemoved(
            companyId: new CompanyId($payload['companyId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
