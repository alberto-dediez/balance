<?php

declare(strict_types=1);

namespace App\EventOperations\Infrastructure\UI\Web\Provider\Bulk;

use App\EventOperations\Application\Provider\Remove\RemoveProviderRequest;
use App\EventOperations\Domain\Model\Provider\Provider;
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

        foreach ($request->elements as $provider) {
            if (!$provider instanceof Provider) {
                continue;
            }

            $commands[] = new RemoveProviderRequest(
                providerId: $provider->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveProviderRequest);

        return new TranslatableMessage('Se ha eliminado el proveedor {id}', ['{id}' => $command->providerId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveProviderRequest);

        return new TranslatableMessage('No se ha podido eliminar el proveedor {id}', ['{id}' => $command->providerId]);
    }
}
