<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Admin\SurplusCause\Bulk;

use App\FoodLoss\Application\SurplusCause\Remove\RemoveSurplusCauseRequest;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCause;
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
        if (0 === count($request->elements)) {
            return [];
        }

        $commands = [];

        foreach ($request->elements as $surplusCause) {
            if (!$surplusCause instanceof SurplusCause) {
                continue;
            }

            $commands[] = new RemoveSurplusCauseRequest(
                surplusCauseId: $surplusCause->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveSurplusCauseRequest);

        return new TranslatableMessage('Se ha eliminado la causa de excedente {id}', ['{id}' => $command->surplusCauseId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveSurplusCauseRequest);

        return new TranslatableMessage('No se ha podido eliminar la causa de excedente {id}', ['{id}' => $command->surplusCauseId]);
    }
}
