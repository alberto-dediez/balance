<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Twig;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPoint;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CriticalPointExtension extends AbstractExtension
{
    /** @var CriticalPoint[] */
    private array $criticalPoints = [];

    public function __construct(private readonly CriticalPointRepository $criticalPointRepository) {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('critical_point_name', $this->criticalPointName(...)),
        ];
    }

    public function criticalPointName(CriticalPointId $criticalPointId): ?CriticalPoint
    {
        if (!isset($this->criticalPoints[(string) $criticalPointId])) {
            $criticalPoint = $this->criticalPointRepository->find($criticalPointId);

            if ($criticalPoint instanceof CriticalPoint) {
                $this->criticalPoints[(string) $criticalPointId] = $criticalPoint;
            }
        }

        return $this->criticalPoints[(string) $criticalPointId] ?? null;
    }
}
