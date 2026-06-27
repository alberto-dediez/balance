<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\Waste\Bulk;

use App\CarbonFootprint\Application\Waste\Remove\RemoveWasteRequest;
use App\CarbonFootprint\Domain\Model\Waste\Waste;
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

        foreach ($request->elements as $waste) {
            if (!$waste instanceof Waste) {
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
        assert($command instanceof RemoveWasteRequest);

        return new TranslatableMessage('Se ha eliminado el residuo {id}', ['{id}' => $command->wasteId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveWasteRequest);

        return new TranslatableMessage('No se ha podido eliminar el residuo {id}', ['{id}' => $command->wasteId]);
    }
}
