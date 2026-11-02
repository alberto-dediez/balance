<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\BaseYearDeclaration;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class BaseYearDeclarationRemoved extends DomainEvent
{
    protected static string $eventName = 'baseYearDeclarationRemoved';

    public function __construct(
        private BaseYearDeclarationId $baseYearDeclarationId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function baseYearDeclarationId(): BaseYearDeclarationId
    {
        return $this->baseYearDeclarationId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->baseYearDeclarationId;
    }

    public function payload(): array
    {
        return [
            'baseYearDeclarationId' => (string) $this->baseYearDeclarationId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            baseYearDeclarationId: new BaseYearDeclarationId($payload['baseYearDeclarationId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
