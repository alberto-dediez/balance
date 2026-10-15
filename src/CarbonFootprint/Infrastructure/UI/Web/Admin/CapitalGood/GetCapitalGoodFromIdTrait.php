<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\CapitalGood;

use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGood;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodId;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRepository;

trait GetCapitalGoodFromIdTrait
{
    private function getCapitalGoodFromId(string $capitalGoodId, CapitalGoodRepository $repository): CapitalGood
    {
        $capitalGood = $repository->find(new CapitalGoodId($capitalGoodId));

        if (null == $capitalGood) {
            throw $this->createNotFoundException('Bien de capital no encontrado');
        }

        return $capitalGood;
    }
}
