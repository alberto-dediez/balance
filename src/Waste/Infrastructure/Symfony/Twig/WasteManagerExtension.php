<?php

namespace App\Waste\Infrastructure\Symfony\Twig;

use Twig\TwigFilter;
use Twig\Extension\AbstractExtension;
use App\Waste\Domain\Model\WasteManager\WasteManager;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use App\Waste\Domain\Model\WasteManager\WasteManagerRepository;

class WasteManagerExtension extends AbstractExtension
{
    private array $wasteManagers = [];

    public function __construct(private WasteManagerRepository $wasteManagerRepository)
    {

    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('waste_manager_name', [$this, 'wasteManagerName']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            
        ];
    }

    public function wasteManagerName(?WasteManagerId $wasteManagerId): ?WasteManager
    {
        if (null == $wasteManagerId) {
            return null;
        }

        if (!isset($wasteManagers[(string) $wasteManagerId])) {
            $wasteManagers[(string) $wasteManagerId] = $this->wasteManagerRepository->find($wasteManagerId);
        }

        return $wasteManagers[(string) $wasteManagerId];
    }
}
