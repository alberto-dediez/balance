<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Web\Admin\Waste\Bulk;

use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\Bulk\RemoveAction;
use App\Waste\Application\Waste\Remove\RemoveWasteRequest;
use Symfony\Component\Translation\TranslatableMessage;

class RemoveWaste implements BulkAction
{
    use RemoveAction;

    public function commands(BulkRequest $request): array
    {
        if (0 === count($request->elements)) {
            return [];
        }

        $commands = [];

        foreach ($request->elements as $waste) {
            if (null === $waste) {
                continue;
            }
            if (null === $waste->id()) {
                continue;
            }
            $commands[] = new RemoveWasteRequest(
                wasteId: $waste->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        return new TranslatableMessage('Se ha eliminado el tipo de residuo {id}', ['{id}' => $command->wasteId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        return new TranslatableMessage('No se ha podido eliminar el tipo de residuo {id}', ['{id}' => $command->wasteId]);
    }
}
