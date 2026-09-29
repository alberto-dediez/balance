<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\MitigationAction;

use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationAction;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionId;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionRepository;

trait GetMitigationActionFromIdTrait
{
    private function getMitigationActionFromId(string $mitigationActionId, MitigationActionRepository $repository): MitigationAction
    {
        $mitigationAction = $repository->find(new MitigationActionId($mitigationActionId));

        if (null == $mitigationAction) {
            throw $this->createNotFoundException('Medida no encontrada');
        }

        return $mitigationAction;
    }
}
