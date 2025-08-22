<?php

namespace App\Waste\Infrastructure\UI\Web\Container\Bulk;

use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use Symfony\Component\Translation\TranslatableMessage;
use App\Waste\Application\Container\Remove\RemoveContainerRequest;

class RemoveContainer implements BulkAction
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

        foreach ($request->elements as $container) {
            if (null === $container || null === $container->id()) {
                continue;
            }

            $commands[] = new RemoveContainerRequest(
                containerId: $container->id()
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        return new TranslatableMessage("Se ha eliminado el contenedor {id}", ['{id}' => $command->containerId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        return new TranslatableMessage("No se ha podido eliminar el contenedor {id}", ['{id}' => $command->containerId]);
    }
}