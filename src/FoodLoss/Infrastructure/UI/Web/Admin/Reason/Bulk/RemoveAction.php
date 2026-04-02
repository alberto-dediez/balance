<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Admin\Reason\Bulk;

use App\FoodLoss\Application\Reason\Remove\RemoveReasonRequest;
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

        foreach ($request->elements as $reason) {
            if (null === $reason || null === $reason->id()) {
                continue;
            }

            $commands[] = new RemoveReasonRequest(
                reasonId: $reason->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveReasonRequest);

        return new TranslatableMessage('Se ha eliminado el motivo {id}', ['{id}' => $command->reasonId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveReasonRequest);

        return new TranslatableMessage('No se ha podido eliminar el motivo {id}', ['{id}' => $command->reasonId]);
    }
}
