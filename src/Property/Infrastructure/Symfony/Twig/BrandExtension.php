<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Symfony\Twig;

use App\Property\Domain\Model\Brand\Brand;
use App\Property\Domain\Model\Brand\BrandId;
use App\Property\Domain\Model\Brand\BrandRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class BrandExtension extends AbstractExtension
{
    /** @var Brand[] */
    private array $brands = [];

    public function __construct(private readonly BrandRepository $brandRepository) {}

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
            $brand = $this->brandRepository->find($brandId);

            if ($brand instanceof Brand) {
                $this->brands[(string) $brandId] = $brand;
            }
        }

        return $this->brands[(string) $brandId];
    }
}
