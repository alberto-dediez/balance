<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Web\Admin\Property\Bulk;

use App\Property\Application\Property\Remove\RemovePropertyRequest;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\Bulk\RemoveAction;
use Symfony\Component\Translation\TranslatableMessage;

class RemoveProperty implements BulkAction
{
    use RemoveAction;

    public function commands(BulkRequest $request): array
    {
        if (0 === count($request->elements)) {
            return [];
        }

        $commands = [];

        foreach ($request->elements as $property) {
            if (null === $property) {
                continue;
            }
            if (null === $property->id()) {
                continue;
            }
            $commands[] = new RemovePropertyRequest(
                propertyId: $property->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        return new TranslatableMessage('Se ha eliminado la propiedad {id}', ['{id}' => $command->propertyId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        return new TranslatableMessage('No se ha podido eliminar la propiedad {id}', ['{id}' => $command->propertyId]);
    }
}
