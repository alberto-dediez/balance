<?php

declare(strict_types=1);

namespace App\Bioscore\Infrastructure\UI\Web\Admin\BioscoreCatalogItem\Bulk;

use App\Bioscore\Application\BioscoreCatalogItem\Unmap\UnmapBioscoreCatalogItemRequest;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItem;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use Symfony\Component\Translation\TranslatableMessage;

class UnmapAction implements BulkAction
{
    public function id(): string
    {
        return 'unmap';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Quitar mapeo');
    }

    public function commands(BulkRequest $request): array
    {
        $commands = [];

        foreach ($request->elements as $item) {
            if (!$item instanceof BioscoreCatalogItem) {
                continue;
            }

            $commands[] = new UnmapBioscoreCatalogItemRequest(itemId: $item->id());
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof UnmapBioscoreCatalogItemRequest);

        return new TranslatableMessage('Se ha quitado el mapeo del elemento de Bioscore {id}', ['{id}' => $command->itemId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof UnmapBioscoreCatalogItemRequest);

        return new TranslatableMessage('No se ha podido quitar el mapeo del elemento de Bioscore {id}', ['{id}' => $command->itemId]);
    }
}
