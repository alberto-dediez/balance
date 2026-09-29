<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\UI\Web\Admin\MaterialitySector\Bulk;

use App\Materiality\Application\MaterialitySector\Remove\RemoveMaterialitySectorRequest;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySector;
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

        foreach ($request->elements as $materialitySector) {
            if (!$materialitySector instanceof MaterialitySector) {
                continue;
            }

            $commands[] = new RemoveMaterialitySectorRequest(
                materialitySectorId: $materialitySector->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveMaterialitySectorRequest);

        return new TranslatableMessage('Se ha eliminado el sector de materialidad {id}', ['{id}' => $command->materialitySectorId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveMaterialitySectorRequest);

        return new TranslatableMessage('No se ha podido eliminar el sector de materialidad {id}', ['{id}' => $command->materialitySectorId]);
    }
}
