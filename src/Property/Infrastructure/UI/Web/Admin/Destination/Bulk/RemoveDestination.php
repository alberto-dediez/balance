<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Web\Admin\Destination\Bulk;

use App\Property\Application\Destination\Remove\RemoveDestinationRequest;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\Bulk\RemoveAction;
use Symfony\Component\Translation\TranslatableMessage;

class RemoveDestination implements BulkAction
{
    use RemoveAction;

    public function commands(BulkRequest $request): array
    {
        if (0 === count($request->elements)) {
            return [];
        }

        $commands = [];

        foreach ($request->elements as $destination) {
            if (null === $destination) {
                continue;
            }
            if (null === $destination->id()) {
                continue;
            }
            $commands[] = new RemoveDestinationRequest(
                destinationId: $destination->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveDestinationRequest);

        return new TranslatableMessage('Se ha eliminado el destino {id}', ['{id}' => $command->destinationId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveDestinationRequest);

        return new TranslatableMessage('No se ha podido eliminar el destino {id}', ['{id}' => $command->destinationId]);
    }
}
