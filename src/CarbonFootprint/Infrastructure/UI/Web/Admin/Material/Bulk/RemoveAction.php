<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\Material\Bulk;

use App\CarbonFootprint\Application\Material\Remove\RemoveMaterialRequest;
use App\CarbonFootprint\Domain\Model\Material\Material;
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

        foreach ($request->elements as $material) {
            if (!$material instanceof Material) {
                continue;
            }

            $commands[] = new RemoveMaterialRequest(
                materialId: $material->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveMaterialRequest);

        return new TranslatableMessage('Se ha eliminado el material {id}', ['{id}' => $command->materialId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveMaterialRequest);

        return new TranslatableMessage('No se ha podido eliminar el material {id}', ['{id}' => $command->materialId]);
    }
}
