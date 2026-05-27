<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Twig;

use App\FoodLoss\Domain\Model\Incidence\Incidence;
use App\FoodLoss\Domain\Model\Incidence\IncidenceId;
use App\FoodLoss\Domain\Model\Incidence\IncidenceRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class IncidenceExtension extends AbstractExtension
{
    /** @var Incidence[] */
    private array $incidences = [];

    public function __construct(private readonly IncidenceRepository $incidenceRepository) {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('incidence_name', $this->incidenceName(...)),
        ];
    }

    public function incidenceName(IncidenceId $incidenceId): ?Incidence
    {
        if (!isset($this->incidences[(string) $incidenceId])) {
            $incidence = $this->incidenceRepository->find($incidenceId);

            if ($incidence instanceof Incidence) {
                $this->incidences[(string) $incidenceId] = $incidence;
            }
        }

        return $this->incidences[(string) $incidenceId] ?? null;
    }
}
