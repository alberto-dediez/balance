<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\OvernightStay;

use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStay;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayId;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepository;

trait GetOvernightStayFromIdTrait
{
    private function getOvernightStayFromId(string $overnightStayId, OvernightStayRepository $repository): OvernightStay
    {
        $overnightStay = $repository->find(new OvernightStayId($overnightStayId));

        if (null == $overnightStay) {
            throw $this->createNotFoundException('Pernoctación no encontrada');
        }

        return $overnightStay;
    }
}
