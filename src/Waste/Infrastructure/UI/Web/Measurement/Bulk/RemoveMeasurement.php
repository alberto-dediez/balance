<?php

namespace App\Waste\Infrastructure\UI\Web\Measurement\Bulk;

use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use Symfony\Component\Translation\TranslatableMessage;
use App\Waste\Application\Measurement\Remove\RemoveMeasurementRequest;

class RemoveMeasurement implements BulkAction
{
    public function id(): string
    {
        return 'remove';
    }

    public function name(): string
    {
        return new TranslatableMessage('Eliminar');
    }

    public function commands(BulkRequest $request): array
    {
        if (count($request->elements) == 0) {
            return [];
        }

        $commands = [];

        foreach ($request->elements as $measurement) {
            if (null === $measurement || null === $measurement->id()) {
                continue;
            }

            $commands[] = new RemoveMeasurementRequest(
                measurementId: $measurement->id()
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        return new TranslatableMessage("Se ha eliminado la medición {id}", ['{id}' => $command->measurementId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        return new TranslatableMessage("No se ha podido eliminar la medición {id}", ['{id}' => $command->measurementId]);
    }
}