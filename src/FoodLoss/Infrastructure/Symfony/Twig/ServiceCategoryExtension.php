<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Twig;

use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategory;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ServiceCategoryExtension extends AbstractExtension
{
    /** @var ServiceCategory[] */
    private array $serviceCategories = [];

    public function __construct(private readonly ServiceCategoryRepository $serviceCategoryRepository) {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('service_category_name', $this->serviceCategoryName(...)),
        ];
    }

    public function serviceCategoryName(ServiceCategoryId $serviceCategoryId): ?ServiceCategory
    {
        if (!isset($this->serviceCategories[(string) $serviceCategoryId])) {
            $serviceCategory = $this->serviceCategoryRepository->find($serviceCategoryId);

            if (null !== $serviceCategory) {
                $this->serviceCategories[(string) $serviceCategoryId] = $serviceCategory;
            }
        }

        return $this->serviceCategories[(string) $serviceCategoryId] ?? null;
    }
}
