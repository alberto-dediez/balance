<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Admin\SurplusCause;

use App\FoodLoss\Domain\Model\SurplusCause\SurplusCause;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseId;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseRepository;

trait GetSurplusCauseFromIdTrait
{
    private function getSurplusCauseFromId(string $surplusCauseId, SurplusCauseRepository $repository): SurplusCause
    {
        $surplusCause = $repository->find(new SurplusCauseId($surplusCauseId));

        if (null == $surplusCause) {
            throw $this->createNotFoundException('La causa de excedente no existe');
        }

        return $surplusCause;
    }
}
