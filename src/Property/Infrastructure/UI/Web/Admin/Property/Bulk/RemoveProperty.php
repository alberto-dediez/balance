<?php

namespace App\Property\Infrastructure\UI\Web\Admin\Property\Bulk;

use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use Symfony\Component\Translation\TranslatableMessage;
use App\Property\Application\Property\Remove\RemovePropertyRequest;

class RemoveProperty implements BulkAction
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

        foreach ($request->elements as $property) {
            if (null === $property || null === $property->id()) {
                continue;
            }

            $commands[] = new RemovePropertyRequest(
                propertyId: $property->id()
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        return new TranslatableMessage("Se ha eliminado la propiedad {id}", ['{id}' => $command->propertyId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        return new TranslatableMessage("No se ha podido eliminar la propiedad {id}", ['{id}' => $command->propertyId]);
    }
}