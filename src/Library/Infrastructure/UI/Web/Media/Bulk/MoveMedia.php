<?php

declare(strict_types=1);

namespace App\Library\Infrastructure\UI\Web\Media\Bulk;

use App\Library\Application\Media\Move\MoveMediaRequest;
use App\Library\Domain\Model\Media\Media;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use Symfony\Component\Translation\TranslatableMessage;

class MoveMedia implements BulkAction
{
    public function id(): string
    {
        return 'move';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Mover a carpeta');
    }

    public function commands(BulkRequest $request): array
    {
        if (0 === count($request->elements)) {
            return [];
        }

        if (!$request instanceof BulkMediaRequest) {
            throw new \RuntimeException('Invalid BulkRequest sent!');
        }

        $commands = [];

        foreach ($request->elements as $media) {
            if (!$media instanceof Media) {
                continue;
            }

            $commands[] = new MoveMediaRequest(
                mediaId: $media->id(),
                folderId: $request->folderId,
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof MoveMediaRequest);

        return new TranslatableMessage('Se ha movido el archivo {id}', ['{id}' => $command->mediaId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof MoveMediaRequest);

        return new TranslatableMessage('No se ha podido mover el archivo {id}', ['{id}' => $command->mediaId]);
    }
}
