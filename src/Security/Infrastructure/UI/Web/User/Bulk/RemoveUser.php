<?php

namespace App\Security\Infrastructure\UI\Web\User\Bulk;

use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\Bulk\RemoveAction;
use Symfony\Component\Translation\TranslatableMessage;
use App\Security\Application\User\Remove\RemoveUserRequest;

class RemoveUser implements BulkAction
{
    use RemoveAction;

    public function commands(BulkRequest $request): array
    {
        if (count($request->elements) === 0) {
            return [];
        }

        $commands = [];

        foreach ($request->elements as $user) {
            if (null === $user) {
                continue;
            }
            if (null === $user->id()) {
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
        assert($command instanceof RemoveUserRequest);

        return new TranslatableMessage("Se ha eliminado el usuario {id}", ['{id}' => $command->userId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveUserRequest);

        return new TranslatableMessage("No se ha podido eliminar el usuario {id}", ['{id}' => $command->userId]);
    }
}
