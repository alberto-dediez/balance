<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Web\Admin\Destination;

use App\Property\Domain\Model\Destination\Destination;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Property\Domain\Model\Destination\DestinationRepository;
use Symfony\Component\Translation\TranslatableMessage;

trait GetDestinationFromIdTrait
{
    private function getDestinationFromId(string $destinationId, DestinationRepository $destinationRepository): Destination
    {
        $destination = $destinationRepository->find(new DestinationId($destinationId));

        if (null == $destination) {
            throw $this->createNotFoundException(new TranslatableMessage('El destino no existe'));
        }

        return $destination;
    }
}
