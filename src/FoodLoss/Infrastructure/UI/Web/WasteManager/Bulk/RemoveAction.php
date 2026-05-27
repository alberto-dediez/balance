<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\WasteManager\Bulk;

use App\FoodLoss\Application\WasteManager\Remove\RemoveWasteManagerRequest;
use App\FoodLoss\Domain\Model\WasteManager\WasteManager;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\Bulk\RemoveAction as RemoveActionTrait;
use Symfony\Component\Translation\TranslatableMessage;

class RemoveAction implements BulkAction
{
    use RemoveActionTrait;

    public function commands(BulkRequest $request): array
    {
        if (0 == count($request->elements)) {
            return [];
        }

        $commands = [];

        foreach ($request->elements as $wasteManager) {
            if (!$wasteManager instanceof WasteManager) {
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

        return new TranslatableMessage('Se ha eliminado el gestor de residuos {id}', ['{id}' => $command->wasteManagerId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveWasteManagerRequest);

        return new TranslatableMessage('No se ha podido eliminar el gestor de residuos {id}', ['{id}' => $command->wasteManagerId]);
    }
}
