<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Surplus\Bulk;

use App\FoodLoss\Application\Surplus\Remove\RemoveSurplusRequest;
use App\FoodLoss\Domain\Model\Surplus\Surplus;
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

        foreach ($request->elements as $surplus) {
            if (!$surplus instanceof Surplus) {
                continue;
            }

            $commands[] = new RemoveSurplusRequest(
                surplusId: $surplus->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveSurplusRequest);

        return new TranslatableMessage('Se ha eliminado el excedente {id}', ['{id}' => $command->surplusId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveSurplusRequest);

        return new TranslatableMessage('No se ha podido eliminar el excedente {id}', ['{id}' => $command->surplusId]);
    }
}
