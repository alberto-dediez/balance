<?php

namespace App\Waste\Infrastructure\UI\Web\WasteManager\Bulk;

use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use Symfony\Component\Translation\TranslatableMessage;
use App\Waste\Application\WasteManager\Remove\RemoveWasteManagerRequest;

class RemoveWasteManager implements BulkAction
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

        foreach ($request->elements as $wasteManager) {
            if (null === $wasteManager || null === $wasteManager->id()) {
                continue;
            }

            $commands[] = new RemoveWasteManagerRequest(
                wasteManagerId: $wasteManager->id()
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        return new TranslatableMessage("Se ha eliminado el gestor {id}", ['{id}' => $command->wasteManagerId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        return new TranslatableMessage("No se ha podido eliminar el gestor {id}", ['{id}' => $command->wasteManagerId]);
    }
}