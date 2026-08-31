<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\Twig;

use App\Shared\Infrastructure\ErrorReport\IncidentIdHolder;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class IncidentExtension extends AbstractExtension
{
    public function __construct(private readonly IncidentIdHolder $incidentIdHolder) {}

    /** @return list<TwigFunction> */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('incident_id', $this->incidentId(...)),
        ];
    }

    public function incidentId(): ?string
    {
        return $this->incidentIdHolder->get();
    }
}
