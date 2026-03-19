<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Web\Container\Bulk;

use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\Bulk\RemoveAction;
use App\Waste\Application\Container\Remove\RemoveContainerRequest;
use Symfony\Component\Translation\TranslatableMessage;

class RemoveContainer implements BulkAction
{
    use RemoveAction;

    public function commands(BulkRequest $request): array
    {
        if (0 === count($request->elements)) {
            return [];
        }

        $commands = [];

        foreach ($request->elements as $container) {
            if (null === $container) {
                continue;
            }
            if (null === $container->id()) {
                continue;
            }
            $commands[] = new RemoveContainerRequest(
                containerId: $container->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        return new TranslatableMessage('Se ha eliminado el contenedor {id}', ['{id}' => $command->containerId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        return new TranslatableMessage('No se ha podido eliminar el contenedor {id}', ['{id}' => $command->containerId]);
    }
}
