<?php

namespace App\Property\Infrastructure\UI\Web\Admin\Destination\Bulk;

use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use Symfony\Component\Translation\TranslatableMessage;
use App\Property\Application\Destination\Remove\RemoveDestinationRequest;

class RemoveDestination implements BulkAction
{
    public function id(): string
    {
        return 'remove';
    }

    public function name(): string
    {
        return new TranslatableMessage('Eliminar');
    }

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