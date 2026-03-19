<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Web\Measurement\Bulk;

use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\Bulk\RemoveAction;
use App\Waste\Application\Measurement\Remove\RemoveMeasurementRequest;
use Symfony\Component\Translation\TranslatableMessage;

class RemoveMeasurement implements BulkAction
{
    use RemoveAction;

    public function commands(BulkRequest $request): array
    {
        if (0 === count($request->elements)) {
            return [];
        }

        $commands = [];

        foreach ($request->elements as $measurement) {
            if (null === $measurement) {
                continue;
            }
            if (null === $measurement->id()) {
                continue;
            }
            $commands[] = new RemoveMeasurementRequest(
                measurementId: $measurement->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        return new TranslatableMessage('Se ha eliminado la medición {id}', ['{id}' => $command->measurementId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        return new TranslatableMessage('No se ha podido eliminar la medición {id}', ['{id}' => $command->measurementId]);
    }
}
