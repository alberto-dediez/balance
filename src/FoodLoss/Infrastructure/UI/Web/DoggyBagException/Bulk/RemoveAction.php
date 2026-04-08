<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\DoggyBagException\Bulk;

use App\FoodLoss\Application\DoggyBagException\Remove\RemoveDoggyBagExceptionRequest;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagException;
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

        foreach ($request->elements as $doggyBagException) {
            if (!$doggyBagException instanceof DoggyBagException) {
                continue;
            }

            $commands[] = new RemoveDoggyBagExceptionRequest(
                doggyBagExceptionId: $doggyBagException->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveDoggyBagExceptionRequest);

        return new TranslatableMessage('Se ha eliminado la excepción Doggy Bag {id}', ['{id}' => $command->doggyBagExceptionId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveDoggyBagExceptionRequest);

        return new TranslatableMessage('No se ha podido eliminar la excepción Doggy Bag {id}', ['{id}' => $command->doggyBagExceptionId]);
    }
}
