<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\RefrigerantGas\Bulk;

use App\CarbonFootprint\Application\RefrigerantGas\Remove\RemoveRefrigerantGasRequest;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGas;
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

        foreach ($request->elements as $refrigerantGas) {
            if (!$refrigerantGas instanceof RefrigerantGas) {
                continue;
            }

            $commands[] = new RemoveRefrigerantGasRequest(
                refrigerantGasId: $refrigerantGas->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveRefrigerantGasRequest);

        return new TranslatableMessage('Se ha eliminado el gas refrigerante {id}', ['{id}' => $command->refrigerantGasId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveRefrigerantGasRequest);

        return new TranslatableMessage('No se ha podido eliminar el gas refrigerante {id}', ['{id}' => $command->refrigerantGasId]);
    }
}
