<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\GdOFactor;

use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactor;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorId;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepository;

trait GetGdOFactorFromIdTrait
{
    private function getGdOFactorFromId(string $gdOFactorId, GdOFactorRepository $repository): GdOFactor
    {
        $gdOFactor = $repository->find(new GdOFactorId($gdOFactorId));

        if (null == $gdOFactor) {
            throw $this->createNotFoundException('Factor GdO no encontrado');
        }

        return $gdOFactor;
    }
}
