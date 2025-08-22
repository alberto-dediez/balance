<?php

namespace App\Waste\Infrastructure\UI\Web\Admin\Waste\Bulk;

use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use Symfony\Component\Translation\TranslatableMessage;
use App\Waste\Application\Waste\Remove\RemoveWasteRequest;

class RemoveWaste implements BulkAction
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

        foreach ($request->elements as $waste) {
            if (null === $waste || null === $waste->id()) {
                continue;
            }

            $commands[] = new RemoveWasteRequest(
                wasteId: $waste->id()
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        return new TranslatableMessage("Se ha eliminado el tipo de residuo {id}", ['{id}' => $command->wasteId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        return new TranslatableMessage("No se ha podido eliminar el tipo de residuo {id}", ['{id}' => $command->wasteId]);
    }
}