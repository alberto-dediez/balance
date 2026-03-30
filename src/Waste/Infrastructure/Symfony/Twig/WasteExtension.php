<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Symfony\Twig;

use App\Waste\Domain\Model\Waste\Waste;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\Waste\WasteRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class WasteExtension extends AbstractExtension
{
    /** @var array<string, Waste|null> */
    private array $wastes = [];

    public function __construct(private readonly WasteRepository $wasteRepository) {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('waste_name', $this->wasteName(...)),
        ];
    }

    public function getFunctions(): array
    {
        return [

        ];
    }

    public function wasteName(WasteId $wasteId): ?Waste
    {
        if (!isset($this->wastes[(string) $wasteId])) {
            $this->wastes[(string) $wasteId] = $this->wasteRepository->find($wasteId);
        }

        return $this->wastes[(string) $wasteId];
    }
}
