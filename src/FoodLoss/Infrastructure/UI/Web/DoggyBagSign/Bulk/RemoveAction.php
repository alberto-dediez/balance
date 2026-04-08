<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\DoggyBagSign\Bulk;

use App\FoodLoss\Application\DoggyBagSign\Remove\RemoveDoggyBagSignRequest;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSign;
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

        foreach ($request->elements as $doggyBagSign) {
            if (!$doggyBagSign instanceof DoggyBagSign) {
                continue;
            }

            $commands[] = new RemoveDoggyBagSignRequest(
                doggyBagSignId: $doggyBagSign->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveDoggyBagSignRequest);

        return new TranslatableMessage('Se ha eliminado el cartel {id}', ['{id}' => $command->doggyBagSignId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveDoggyBagSignRequest);

        return new TranslatableMessage('No se ha podido eliminar el cartel {id}', ['{id}' => $command->doggyBagSignId]);
    }
}
