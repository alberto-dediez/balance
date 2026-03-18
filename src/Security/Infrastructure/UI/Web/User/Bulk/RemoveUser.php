<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\User\Bulk;

use App\Security\Application\User\Remove\RemoveUserRequest;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\Bulk\RemoveAction;
use Symfony\Component\Translation\TranslatableMessage;

class RemoveUser implements BulkAction
{
    use RemoveAction;

    public function commands(BulkRequest $request): array
    {
        if (0 === count($request->elements)) {
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
                userId: $user->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveUserRequest);

        return new TranslatableMessage('Se ha eliminado el usuario {id}', ['{id}' => $command->userId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveUserRequest);

        return new TranslatableMessage('No se ha podido eliminar el usuario {id}', ['{id}' => $command->userId]);
    }
}
