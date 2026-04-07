<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Web\Admin\Property\Bulk;

use App\Property\Application\Property\Update\UpdatePropertyRequest;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use Symfony\Component\Translation\TranslatableMessage;

class AssignAccount implements BulkAction
{
    public function id(): string
    {
        return 'assign-account';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Asignar cuenta');
    }

    public function commands(BulkRequest $request): array
    {
        if (0 === count($request->elements)) {
            return [];
        }

        if (!$request instanceof BulkPropertyRequest) {
            throw new \RuntimeException('Invalid BulkRequest sent!');
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
                externalId: $property->externalId(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof UpdatePropertyRequest);

        $assignAccount = $command->accountId instanceof AccountId;

        return $assignAccount
            ? new TranslatableMessage('Se ha asignado la cuenta seleccionada a la propiedad {id}', ['{id}' => $command->propertyId])
            : new TranslatableMessage('Se ha eliminado la cuenta de la propiedad {id}', ['{id}' => $command->propertyId])
        ;
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof UpdatePropertyRequest);

        return new TranslatableMessage('No se ha podido modificar la propiedad {id}', ['{id}' => $command->propertyId]);
    }
}
