<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\Transport;

use App\CarbonFootprint\Domain\Model\Transport\Transport;
use App\CarbonFootprint\Domain\Model\Transport\TransportId;
use App\CarbonFootprint\Domain\Model\Transport\TransportRepository;

trait GetTransportFromIdTrait
{
    private function getTransportFromId(string $transportId, TransportRepository $repository): Transport
    {
        $transport = $repository->find(new TransportId($transportId));

        if (null == $transport) {
            throw $this->createNotFoundException('Medio de transporte no encontrado');
        }

        return $transport;
    }
}
