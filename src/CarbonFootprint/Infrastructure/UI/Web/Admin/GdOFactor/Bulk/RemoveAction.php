<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\GdOFactor\Bulk;

use App\CarbonFootprint\Application\GdOFactor\Remove\RemoveGdOFactorRequest;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactor;
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

        foreach ($request->elements as $gdOFactor) {
            if (!$gdOFactor instanceof GdOFactor) {
                continue;
            }

            $commands[] = new RemoveGdOFactorRequest(
                gdOFactorId: $gdOFactor->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveGdOFactorRequest);

        return new TranslatableMessage('Se ha eliminado el factor GdO {id}', ['{id}' => $command->gdOFactorId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveGdOFactorRequest);

        return new TranslatableMessage('No se ha podido eliminar el factor GdO {id}', ['{id}' => $command->gdOFactorId]);
    }
}
