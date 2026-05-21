<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Surplus;

use App\FoodLoss\Domain\Model\Surplus\Surplus;
use App\FoodLoss\Domain\Model\Surplus\SurplusId;
use App\FoodLoss\Domain\Model\Surplus\SurplusRepository;

trait GetSurplusFromIdTrait
{
    private function getSurplusFromId(string $surplusId, SurplusRepository $repository): Surplus
    {
        $surplus = $repository->find(new SurplusId($surplusId));

        if (null == $surplus) {
            throw $this->createNotFoundException('Excedente no encontrado');
        }

        return $surplus;
    }
}
