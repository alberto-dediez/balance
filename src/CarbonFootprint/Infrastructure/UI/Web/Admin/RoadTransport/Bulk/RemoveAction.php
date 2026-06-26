<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\RoadTransport\Bulk;

use App\CarbonFootprint\Application\RoadTransport\Remove\RemoveRoadTransportRequest;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransport;
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

        foreach ($request->elements as $roadTransport) {
            if (!$roadTransport instanceof RoadTransport) {
                continue;
            }

            $commands[] = new RemoveRoadTransportRequest(
                roadTransportId: $roadTransport->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveRoadTransportRequest);

        return new TranslatableMessage('Se ha eliminado el transporte {id}', ['{id}' => $command->roadTransportId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveRoadTransportRequest);

        return new TranslatableMessage('No se ha podido eliminar el transporte {id}', ['{id}' => $command->roadTransportId]);
    }
}
