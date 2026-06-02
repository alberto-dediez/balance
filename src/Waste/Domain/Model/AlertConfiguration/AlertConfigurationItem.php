<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\AlertConfiguration;

use App\Waste\Domain\Model\Alert\AlertType;

class AlertConfigurationItem
{
    /** @param array<string, mixed> $parameters */
    public function __construct(
        private AlertConfigurationItemId $id,
        private AlertConfiguration $alertConfiguration,
        private AlertType $type,
        private bool $enabled = false,
        private array $parameters = [],
    ) {}

    public function id(): AlertConfigurationItemId
    {
        return $this->id;
    }

    public function alertConfiguration(): AlertConfiguration
    {
        return $this->alertConfiguration;
    }

    public function type(): AlertType
    {
        return $this->type;
    }

    public function enabled(): bool
    {
        return $this->enabled;
    }

    /** @return array<string, mixed> */
    public function parameters(): array
    {
        return $this->parameters;
    }

    /** @param array<string, mixed> $parameters */
    public function update(bool $enabled, array $parameters): void
    {
        $this->enabled = $enabled;
        $this->parameters = $parameters;
    }
}
