<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\MerchandisingItem\Bulk;

use App\CarbonFootprint\Application\MerchandisingItem\Remove\RemoveMerchandisingItemRequest;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItem;
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

        foreach ($request->elements as $merchandisingItem) {
            if (!$merchandisingItem instanceof MerchandisingItem) {
                continue;
            }

            $commands[] = new RemoveMerchandisingItemRequest(
                merchandisingItemId: $merchandisingItem->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveMerchandisingItemRequest);

        return new TranslatableMessage('Se ha eliminado el artículo de merchandising {id}', ['{id}' => $command->merchandisingItemId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveMerchandisingItemRequest);

        return new TranslatableMessage('No se ha podido eliminar el artículo de merchandising {id}', ['{id}' => $command->merchandisingItemId]);
    }
}
