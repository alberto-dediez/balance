<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\Mobility\Bulk;

use App\CarbonFootprint\Application\Mobility\Remove\RemoveMobilityRequest;
use App\CarbonFootprint\Domain\Model\Mobility\Mobility;
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

        foreach ($request->elements as $mobility) {
            if (!$mobility instanceof Mobility) {
                continue;
            }

            $commands[] = new RemoveMobilityRequest(
                mobilityId: $mobility->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveMobilityRequest);

        return new TranslatableMessage('Se ha eliminado la movilidad {id}', ['{id}' => $command->mobilityId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveMobilityRequest);

        return new TranslatableMessage('No se ha podido eliminar la movilidad {id}', ['{id}' => $command->mobilityId]);
    }
}
