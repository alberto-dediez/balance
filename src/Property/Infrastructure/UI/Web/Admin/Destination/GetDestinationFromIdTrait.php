<?php

namespace App\Property\Infrastructure\UI\Web\Admin\Destination;

use App\Property\Domain\Model\Destination\Destination;
use App\Property\Domain\Model\Destination\DestinationId;
use Symfony\Component\Translation\TranslatableMessage;
use App\Property\Domain\Model\Destination\DestinationRepository;

trait GetDestinationFromIdTrait
{
    private function getDestinationFromId(string $destinationId, DestinationRepository $destinationRepository): Destination
    {
        $destination = $destinationRepository->find(new DestinationId($destinationId));

        if (null == $destination) {
            throw $this->createNotFoundException(new TranslatableMessage("El destino no existe"));
        }

        return $destination;
    }
}