<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Protocol;

use App\FoodLoss\Domain\Model\Protocol\Protocol;
use App\FoodLoss\Domain\Model\Protocol\ProtocolId;
use App\FoodLoss\Domain\Model\Protocol\ProtocolRepository;

trait GetProtocolFromIdTrait
{
    private function getProtocolFromId(string $protocolId, ProtocolRepository $repository): Protocol
    {
        $protocol = $repository->find(new ProtocolId($protocolId));

        if (null == $protocol) {
            throw $this->createNotFoundException('Protocolo no encontrado');
        }

        return $protocol;
    }
}
