<?php

namespace App\Property\Infrastructure\UI\Web\Admin\Property\Bulk;

use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use Symfony\Component\Translation\TranslatableMessage;
use App\Property\Application\Property\Update\UpdatePropertyRequest;
use App\Property\Infrastructure\UI\Web\Admin\Property\Bulk\BulkPropertyRequest;

class AssignAccount implements BulkAction
{
    public function id(): string
    {
        return 'assign-account';
    }

    public function name(): string
    {
        return new TranslatableMessage('Asignar cuenta');
    }

    public function commands(BulkRequest $request): array
    {
        if (count($request->elements) === 0) {
            return [];
        }

        if (!$request instanceof BulkPropertyRequest) {
            throw new \RuntimeException("Invalid BulkRequest sent!");
        }

        $commands = [];

        foreach ($request->elements as $property) {
            if (null === $property) {
                continue;
            }
            if (null === $property->id()) {
                continue;
            }
            $commands[] = new UpdatePropertyRequest(
                propertyId: $property->id(),
                name: $property->name(),
                accountId: $request->accountId,
                info: $property->info(),
                externalId: $property->externalId()
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        $assignAccount = $command->accountId !== null;

        return $assignAccount 
            ? new TranslatableMessage("Se ha asignado la cuenta seleccionada a la propiedad {id}", ['{id}' => $command->propertyId])
            : new TranslatableMessage("Se ha eliminado la cuenta de la propiedad {id}", ['{id}' => $command->propertyId])
        ;
    }

    public function failure(Command $command): TranslatableMessage
    {
        return new TranslatableMessage("No se ha podido modificar la propiedad {id}", ['{id}' => $command->propertyId]);
    }
}