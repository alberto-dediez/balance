<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Admin\Action\Bulk;

use App\FoodLoss\Application\Action\Remove\RemoveActionRequest;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use Symfony\Component\Translation\TranslatableMessage;

class RemoveAction implements BulkAction
{
    public function id(): string
    {
        return 'remove';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Eliminar');
    }

    public function commands(BulkRequest $request): array
    {
        if (0 == count($request->elements)) {
            return [];
        }

        $commands = [];

        foreach ($request->elements as $action) {
            if (null === $action || null === $action->id()) {
                continue;
            }

            $commands[] = new RemoveActionRequest(
                actionId: $action->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveActionRequest);

        return new TranslatableMessage('Se ha eliminado la acción {id}', ['{id}' => $command->actionId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveActionRequest);

        return new TranslatableMessage('No se ha podido eliminar la acción {id}', ['{id}' => $command->actionId]);
    }
}
