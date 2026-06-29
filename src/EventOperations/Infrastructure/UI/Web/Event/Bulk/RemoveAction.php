<?php

declare(strict_types=1);

namespace App\EventOperations\Infrastructure\UI\Web\Event\Bulk;

use App\EventOperations\Application\Event\Remove\RemoveEventRequest;
use App\EventOperations\Domain\Model\Event\Event;
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

        foreach ($request->elements as $event) {
            if (!$event instanceof Event) {
                continue;
            }

            $commands[] = new RemoveEventRequest(
                eventId: $event->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveEventRequest);

        return new TranslatableMessage('Se ha eliminado el evento {id}', ['{id}' => $command->eventId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveEventRequest);

        return new TranslatableMessage('No se ha podido eliminar el evento {id}', ['{id}' => $command->eventId]);
    }
}
