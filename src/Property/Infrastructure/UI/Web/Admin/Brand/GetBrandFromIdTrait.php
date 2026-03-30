<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Web\Admin\Brand;

use App\Property\Domain\Model\Brand\Brand;
use App\Property\Domain\Model\Brand\BrandId;
use App\Property\Domain\Model\Brand\BrandRepository;
use Symfony\Component\Translation\TranslatableMessage;

trait GetBrandFromIdTrait
{
    private function getBrandFromId(string $brandId, BrandRepository $brandRepository): Brand
    {
        $brand = $brandRepository->find(new BrandId($brandId));

        if (null == $brand) {
            throw $this->createNotFoundException((string) new TranslatableMessage('La marca no existe'));
        }

        return $brand;
    }
}
