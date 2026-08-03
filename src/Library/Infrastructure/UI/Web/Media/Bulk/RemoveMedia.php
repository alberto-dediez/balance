<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\UI\Web\Media\Bulk;

use App\Library\Application\Media\Remove\RemoveMediaRequest;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\Bulk\RemoveAction;
use Symfony\Component\Translation\TranslatableMessage;

class RemoveMedia implements BulkAction
{
    use RemoveAction;

    public function commands(BulkRequest $request): array
    {
        if (0 === count($request->elements)) {
            return [];
        }

        $commands = [];

        foreach ($request->elements as $media) {
            if (null === $media) {
                continue;
            }

            $commands[] = new RemoveMediaRequest(
                mediaId: $media->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveMediaRequest);

        return new TranslatableMessage('Se ha eliminado el archivo {id}', ['{id}' => $command->mediaId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveMediaRequest);

        return new TranslatableMessage('No se ha podido eliminar el archivo {id}', ['{id}' => $command->mediaId]);
    }
}
