<?php

namespace App\Security\Infrastructure\UI\Web\Admin\Account\Bulk;

use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\Bulk\RemoveAction;
use Symfony\Component\Translation\TranslatableMessage;
use App\Security\Application\Account\Remove\RemoveAccountRequest;

class RemoveAccount implements BulkAction
{
    use RemoveAction;

    public function commands(BulkRequest $request): array
    {
        if (count($request->elements) === 0) {
            return [];
        }

        $commands = [];

        foreach ($request->elements as $account) {
            if (null === $account) {
                continue;
            }
            if (null === $account->id()) {
                continue;
            }
            $commands[] = new RemoveAccountRequest(
                accountId: $account->id()
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveAccountRequest);

        return new TranslatableMessage("Se ha eliminado la cuenta {id}", ['{id}' => $command->accountId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveAccountRequest);

        return new TranslatableMessage("No se ha podido eliminar la cuenta {id}", ['{id}' => $command->accountId]);
    }
}
