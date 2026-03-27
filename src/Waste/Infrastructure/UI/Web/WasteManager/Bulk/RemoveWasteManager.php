<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Web\WasteManager\Bulk;

use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\Bulk\RemoveAction;
use App\Waste\Application\WasteManager\Remove\RemoveWasteManagerRequest;
use Symfony\Component\Translation\TranslatableMessage;

class RemoveWasteManager implements BulkAction
{
    use RemoveAction;

    public function commands(BulkRequest $request): array
    {
        if (0 === count($request->elements)) {
            return [];
        }

        $commands = [];

        foreach ($request->elements as $wasteManager) {
            if (null === $wasteManager) {
                continue;
            }
            if (null === $wasteManager->id()) {
                continue;
            }
            $commands[] = new RemoveWasteManagerRequest(
                wasteManagerId: $wasteManager->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveWasteManagerRequest);

        return new TranslatableMessage('Se ha eliminado el gestor {id}', ['{id}' => $command->wasteManagerId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveWasteManagerRequest);

        return new TranslatableMessage('No se ha podido eliminar el gestor {id}', ['{id}' => $command->wasteManagerId]);
    }
}
