<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EmissionFactorGroup;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class EmissionFactorGroupRegistered extends DomainEvent
{
    protected static string $eventName = 'emissionFactorGroupRegistered';

    public function __construct(
        private EmissionFactorGroupId $emissionFactorGroupId,
        private string $code,
        private string $name,
        private Gwp $gwp,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function emissionFactorGroupId(): EmissionFactorGroupId
    {
        return $this->emissionFactorGroupId;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function gwp(): Gwp
    {
        return $this->gwp;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->emissionFactorGroupId();
    }

    public function payload(): array
    {
        return [
            'emissionFactorGroupId' => (string) $this->emissionFactorGroupId,
            'code' => $this->code,
            'name' => $this->name,
            'gwpCo2' => $this->gwp->gwpCo2(),
            'gwpCh4' => $this->gwp->gwpCh4(),
            'gwpN2O' => $this->gwp->gwpN2O(),
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new EmissionFactorGroupRegistered(
            emissionFactorGroupId: new EmissionFactorGroupId($payload['emissionFactorGroupId']),
            code: $payload['code'],
            name: $payload['name'],
            gwp: new Gwp(
                gwpCo2: (float) $payload['gwpCo2'],
                gwpCh4: (float) $payload['gwpCh4'],
                gwpN2O: (float) $payload['gwpN2O'],
            ),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
