<?php

namespace App\FoodLoss\Infrastructure\UI\Web\Admin\ServiceCategory;

use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategory;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use Symfony\Component\Translation\TranslatableMessage;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepository;

trait GetServiceCategoryFromIdTrait
{
    private function getServiceCategoryFromId(string $serviceCategoryId, ServiceCategoryRepository $repository): ServiceCategory
    {
        $serviceCategory = $repository->find(new ServiceCategoryId($serviceCategoryId));

        if (null == $serviceCategory) {
            throw $this->createNotFoundException(new TranslatableMessage("La categoría de servicio no existe"));
        }

        return $serviceCategory;
    }
}