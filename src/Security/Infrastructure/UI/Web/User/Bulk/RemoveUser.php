<?php

namespace App\Security\Infrastructure\UI\Web\User\Bulk;

use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use Symfony\Component\Translation\TranslatableMessage;
use App\Security\Application\User\Remove\RemoveUserRequest;

class RemoveUser implements BulkAction
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

        foreach ($request->elements as $user) {
            if (null === $user || null === $user->id()) {
                continue;
            }

            $commands[] = new RemoveUserRequest(
                userId: $user->id()
            );
        }

        return $commands;
    }
    
    public function success(Command $command): TranslatableMessage
    {
        return new TranslatableMessage("Se ha eliminado el usuario {id}", ['{id}' => $command->userId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        return new TranslatableMessage("No se ha podido eliminar el usuario {id}", ['{id}' => $command->userId]);
    }
}