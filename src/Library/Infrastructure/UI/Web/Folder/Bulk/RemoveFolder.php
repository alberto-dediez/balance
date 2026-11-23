<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\UI\Web\Folder\Bulk;

use App\Library\Application\Folder\Remove\RemoveFolderRequest;
use App\Library\Domain\Model\Folder\Folder;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\Bulk\RemoveAction;
use Symfony\Component\Translation\TranslatableMessage;

class RemoveFolder implements BulkAction
{
    use RemoveAction;

    public function commands(BulkRequest $request): array
    {
        if (0 === count($request->elements)) {
            return [];
        }

        $commands = [];

        foreach ($request->elements as $folder) {
            if (!$folder instanceof Folder) {
                continue;
            }

            $commands[] = new RemoveFolderRequest(
                folderId: $folder->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveFolderRequest);

        return new TranslatableMessage('Se ha eliminado la carpeta {id}', ['{id}' => $command->folderId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveFolderRequest);

        return new TranslatableMessage('No se ha podido eliminar la carpeta {id}', ['{id}' => $command->folderId]);
    }
}
