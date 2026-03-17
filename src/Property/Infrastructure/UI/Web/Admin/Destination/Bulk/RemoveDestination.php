<?php

namespace App\Property\Infrastructure\UI\Web\Admin\Destination\Bulk;

use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\Bulk\RemoveAction;
use Symfony\Component\Translation\TranslatableMessage;
use App\Property\Application\Destination\Remove\RemoveDestinationRequest;

class RemoveDestination implements BulkAction
{
    use RemoveAction;

    public function commands(BulkRequest $request): array
    {
        if (count($request->elements) == 0) {
            return [];
        }

        $commands = [];

        foreach ($request->elements as $destination) {
            if (null === $destination || null === $destination->id()) {
                continue;
            }

            $commands[] = new RemoveDestinationRequest(
                destinationId: $destination->id()
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        return new TranslatableMessage("Se ha eliminado el destino {id}", ['{id}' => $command->destinationId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        return new TranslatableMessage("No se ha podido eliminar el destino {id}", ['{id}' => $command->destinationId]);
    }
}
