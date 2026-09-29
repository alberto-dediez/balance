<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\UI\Web\Twig;

use App\Materiality\Domain\Model\MaterialitySector\MaterialitySector;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MaterialitySectorNameExtension extends AbstractExtension
{
    /** @var array<string, ?MaterialitySector> */
    private array $materialitySectors = [];

    public function __construct(private readonly MaterialitySectorRepository $materialitySectorRepository) {}

    /** @return TwigFilter[] */
    public function getFilters(): array
    {
        return [
            new TwigFilter('materiality_sector_name', $this->materialitySectorName(...)),
        ];
    }

    public function materialitySectorName(MaterialitySectorId $materialitySectorId): ?MaterialitySector
    {
        if (!array_key_exists((string) $materialitySectorId, $this->materialitySectors)) {
            $this->materialitySectors[(string) $materialitySectorId] = $this->materialitySectorRepository->find($materialitySectorId);
        }

        return $this->materialitySectors[(string) $materialitySectorId];
    }
}
