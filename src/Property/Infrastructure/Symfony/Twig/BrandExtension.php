<?php

namespace App\Property\Infrastructure\Symfony\Twig;

use Twig\TwigFilter;
use Twig\Extension\AbstractExtension;
use App\Property\Domain\Model\Brand\Brand;
use App\Property\Domain\Model\Brand\BrandId;
use App\Property\Domain\Model\Brand\BrandRepository;

class BrandExtension extends AbstractExtension
{
    private array $brands = [];

    public function __construct(private readonly BrandRepository $brandRepository)
    {

    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('brand_name', $this->brandName(...)),
        ];
    }

    public function getFunctions(): array
    {
        return [
            
        ];
    }

    public function brandName(?BrandId $brandId): ?Brand
    {
        if (!$brandId instanceof BrandId) {
            return null;
        }

        if (!isset($this->brands[(string) $brandId])) {
            $this->brands[(string) $brandId] = $this->brandRepository->find($brandId);
        }

        return $this->brands[(string) $brandId];
    }
}
