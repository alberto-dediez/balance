<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\ErrorReport;

use Symfony\Contracts\Service\ResetInterface;

class IncidentIdHolder implements ResetInterface
{
    private ?string $incidentId = null;

    public function set(string $incidentId): void
    {
        $this->incidentId = $incidentId;
    }

    public function get(): ?string
    {
        return $this->incidentId;
    }

    public function reset(): void
    {
        $this->incidentId = null;
    }
}
