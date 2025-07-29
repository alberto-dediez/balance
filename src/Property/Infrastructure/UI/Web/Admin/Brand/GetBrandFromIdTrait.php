<?php

namespace App\Property\Infrastructure\UI\Web\Admin\Brand;

use App\Property\Domain\Model\Brand\Brand;
use App\Property\Domain\Model\Brand\BrandId;
use Symfony\Component\Translation\TranslatableMessage;
use App\Property\Domain\Model\Brand\BrandRepository;

trait GetBrandFromIdTrait
{
    private function getBrandFromId(string $brandId, BrandRepository $brandRepository): Brand
    {
        $brand = $brandRepository->find(new BrandId($brandId));

        if (null == $brand) {
            throw $this->createNotFoundException(new TranslatableMessage("La marca no existe"));
        }

        return $brand;
    }
}