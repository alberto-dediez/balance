<?php

namespace App\Security\Infrastructure\UI\Web\User\Bulk;

use App\Security\Application\User\Remove\RemoveUserRequest;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use Symfony\Component\Translation\TranslatableMessage;
use App\Security\Application\User\GrantRole\GrantRoleRequest;
use App\Security\Infrastructure\UI\Web\User\Bulk\BulkUserRequest;

class GrantRole implements BulkAction
{
    public function id(): string
    {
        return 'grant-role';
    }

    public function name(): string
    {
        return new TranslatableMessage('Asignar rol');
    }

    public function commands(BulkRequest $request): array
    {
        if (count($request->elements) == 0) {
            return [];
        }

        if (!$request instanceof BulkUserRequest) {
            throw new \RuntimeException("Invalid BulkRequest sent!");
        }

        $commands = [];

        foreach ($request->elements as $user) {
            if (null === $user || null === $user->id()) {
                continue;
            }

            $commands[] = new GrantRoleRequest(
                userId: $user->id(),
                role: $request->role
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof GrantRoleRequest);

        return new TranslatableMessage("Se ha añadido el rol seleccionado al usuario {id}", ['{id}' => $command->userId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof GrantRoleRequest);

        return new TranslatableMessage("No se ha podido añadir el rol seleccionado al usuario {id}", ['{id}' => $command->userId]);
    }
}
