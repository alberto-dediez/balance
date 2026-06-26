<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\Mobility;

use App\CarbonFootprint\Domain\Model\Mobility\Mobility;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;

trait GetMobilityFromIdTrait
{
    private function getMobilityFromId(string $mobilityId, MobilityRepository $repository): Mobility
    {
        $mobility = $repository->find(new MobilityId($mobilityId));

        if (null == $mobility) {
            throw $this->createNotFoundException('Movilidad no encontrada');
        }

        return $mobility;
    }
}
