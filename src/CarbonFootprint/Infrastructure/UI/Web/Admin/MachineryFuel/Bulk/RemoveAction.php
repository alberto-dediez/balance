<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\MachineryFuel\Bulk;

use App\CarbonFootprint\Application\MachineryFuel\Remove\RemoveMachineryFuelRequest;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuel;
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

        foreach ($request->elements as $machineryFuel) {
            if (!$machineryFuel instanceof MachineryFuel) {
                continue;
            }

            $commands[] = new RemoveMachineryFuelRequest(
                machineryFuelId: $machineryFuel->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveMachineryFuelRequest);

        return new TranslatableMessage('Se ha eliminado el combustible de maquinaria {id}', ['{id}' => $command->machineryFuelId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveMachineryFuelRequest);

        return new TranslatableMessage('No se ha podido eliminar el combustible de maquinaria {id}', ['{id}' => $command->machineryFuelId]);
    }
}
