<?php

namespace App\Waste\Domain\Model\Waste;

use App\Shared\Domain\Model\AggregateRoot;

class Waste
{
    use AggregateRoot;

    protected array $events = [];

    public function __construct(
        protected WasteId $id, 
        protected string $name = '',
        protected bool $isDangerous = false,
        protected string $density = '0.00',
        protected string $color = '#c3d300',
        protected bool $removed = false,
        array $events = [],
        protected ?\DateTimeImmutable $registeredAt = null,
        protected ?\DateTimeImmutable $removedAt = null
    ) {

    }

    public function id(): WasteId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isDangerous(): bool
    {
        return $this->isDangerous;
    }

    public function density(): string
    {
        return $this->density;
    }

    public function color(): string
    {
        return $this->color;
    }

    public function isRemoved(): bool
    {
        return $this->removed;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function removedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }

    public function register(
        string $name, 
        bool $isDangerous = false,
        string $density = '0.00',
        string $color = '#c3d300'
    ) {
        $this->recordApplyAndPublish(
            new WasteRegistered(
                wasteId: $this->id(),
                name: $name,
                isDangerous: $isDangerous,
                density: $density,
                color: $color
            )
        );
    }

    public function remove()
    {
        $this->recordApplyAndPublish(
            new WasteRemoved(
                wasteId: $this->id()
            )
        );
    }

    public function applyWasteRegistered(WasteRegistered $event)
    {
        $this->name = $event->name();
        $this->isDangerous = $event->isDangerous();
        $this->density = $event->density();
        $this->color = $event->color();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyWasteRemoved(WasteRemoved $event)
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return (string) $this->name();
    }
}