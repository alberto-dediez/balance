<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Symfony\Twig;

use App\Waste\Domain\Model\WasteManager\WasteManager;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use App\Waste\Domain\Model\WasteManager\WasteManagerRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class WasteManagerExtension extends AbstractExtension
{
    public function __construct(private readonly WasteManagerRepository $wasteManagerRepository) {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('waste_manager_name', $this->wasteManagerName(...)),
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
