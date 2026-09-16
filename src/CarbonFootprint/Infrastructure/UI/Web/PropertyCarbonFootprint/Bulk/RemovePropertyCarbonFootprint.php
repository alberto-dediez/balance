<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\PropertyCarbonFootprint\Bulk;

use App\CarbonFootprint\Application\PropertyCarbonFootprint\Remove\RemovePropertyCarbonFootprintRequest;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\Bulk\RemoveAction;
use Symfony\Component\Translation\TranslatableMessage;

class RemovePropertyCarbonFootprint implements BulkAction
{
    use RemoveAction;

    public function commands(BulkRequest $request): array
    {
        $commands = [];

        foreach ($request->elements as $propertyCarbonFootprint) {
            if (null === $propertyCarbonFootprint) {
                continue;
            }

            $commands[] = new RemovePropertyCarbonFootprintRequest(
                propertyCarbonFootprintId: $propertyCarbonFootprint->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemovePropertyCarbonFootprintRequest);

        return new TranslatableMessage('Se ha eliminado el cálculo de huella de carbono {id}', ['{id}' => $command->propertyCarbonFootprintId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemovePropertyCarbonFootprintRequest);

        return new TranslatableMessage('No se ha podido eliminar el cálculo de huella de carbono {id}', ['{id}' => $command->propertyCarbonFootprintId]);
    }
}
