<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\CateringItem\Bulk;

use App\CarbonFootprint\Application\CateringItem\Remove\RemoveCateringItemRequest;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItem;
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

        foreach ($request->elements as $cateringItem) {
            if (!$cateringItem instanceof CateringItem) {
                continue;
            }

            $commands[] = new RemoveCateringItemRequest(
                cateringItemId: $cateringItem->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveCateringItemRequest);

        return new TranslatableMessage('Se ha eliminado el elemento de catering {id}', ['{id}' => $command->cateringItemId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveCateringItemRequest);

        return new TranslatableMessage('No se ha podido eliminar el elemento de catering {id}', ['{id}' => $command->cateringItemId]);
    }
}
