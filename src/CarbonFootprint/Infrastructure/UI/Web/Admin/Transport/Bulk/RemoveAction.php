<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\Transport\Bulk;

use App\CarbonFootprint\Application\Transport\Remove\RemoveTransportRequest;
use App\CarbonFootprint\Domain\Model\Transport\Transport;
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

        foreach ($request->elements as $transport) {
            if (!$transport instanceof Transport) {
                continue;
            }

            $commands[] = new RemoveTransportRequest(
                transportId: $transport->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveTransportRequest);

        return new TranslatableMessage('Se ha eliminado el transporte {id}', ['{id}' => $command->transportId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveTransportRequest);

        return new TranslatableMessage('No se ha podido eliminar el transporte {id}', ['{id}' => $command->transportId]);
    }
}
