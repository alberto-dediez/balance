<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\CompanyCarbonFootprint\Bulk;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\Remove\RemoveCompanyCarbonFootprintRequest;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\Bulk\RemoveAction;
use Symfony\Component\Translation\TranslatableMessage;

class RemoveCompanyCarbonFootprint implements BulkAction
{
    use RemoveAction;

    public function commands(BulkRequest $request): array
    {
        $commands = [];

        foreach ($request->elements as $companyCarbonFootprint) {
            if (null === $companyCarbonFootprint) {
                continue;
            }

            $commands[] = new RemoveCompanyCarbonFootprintRequest(
                companyCarbonFootprintId: $companyCarbonFootprint->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveCompanyCarbonFootprintRequest);

        return new TranslatableMessage('Se ha eliminado el cálculo de huella de carbono {id}', ['{id}' => $command->companyCarbonFootprintId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveCompanyCarbonFootprintRequest);

        return new TranslatableMessage('No se ha podido eliminar el cálculo de huella de carbono {id}', ['{id}' => $command->companyCarbonFootprintId]);
    }
}
