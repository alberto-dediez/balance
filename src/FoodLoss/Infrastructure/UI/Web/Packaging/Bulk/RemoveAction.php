<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Packaging\Bulk;

use App\FoodLoss\Application\Packaging\Remove\RemovePackagingRequest;
use App\FoodLoss\Domain\Model\Packaging\Packaging;
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

        foreach ($request->elements as $packaging) {
            if (!$packaging instanceof Packaging) {
                continue;
            }

            $commands[] = new RemovePackagingRequest(
                packagingId: $packaging->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemovePackagingRequest);

        return new TranslatableMessage('Se ha eliminado el envase {id}', ['{id}' => $command->packagingId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemovePackagingRequest);

        return new TranslatableMessage('No se ha podido eliminar el envase {id}', ['{id}' => $command->packagingId]);
    }
}
