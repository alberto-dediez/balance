<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Packaging;

use App\FoodLoss\Domain\Model\Packaging\Packaging;
use App\FoodLoss\Domain\Model\Packaging\PackagingId;
use App\FoodLoss\Domain\Model\Packaging\PackagingRepository;

trait GetPackagingFromIdTrait
{
    private function getPackagingFromId(string $packagingId, PackagingRepository $repository): Packaging
    {
        $packaging = $repository->find(new PackagingId($packagingId));

        if (null == $packaging) {
            throw $this->createNotFoundException('Envase no encontrado');
        }

        return $packaging;
    }
}
