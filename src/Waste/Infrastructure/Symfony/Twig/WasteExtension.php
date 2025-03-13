<?php

namespace App\Waste\Infrastructure\Symfony\Twig;

use Twig\TwigFilter;
use Twig\Extension\AbstractExtension;
use App\Waste\Domain\Model\Waste\Waste;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\Waste\WasteRepository;

class WasteExtension extends AbstractExtension
{
    private array $wastes = [];

    public function __construct(private WasteRepository $wasteRepository)
    {

    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('waste_name', [$this, 'wasteName']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            
        ];
    }

    public function wasteName(WasteId $wasteId): ?Waste
    {
        if (!isset($wastes[(string) $wasteId])) {
            $wastes[(string) $wasteId] = $this->wasteRepository->find($wasteId);
        }

        return $wastes[(string) $wasteId];
    }
}
