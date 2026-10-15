<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\CapitalGood\Bulk;

use App\CarbonFootprint\Application\CapitalGood\Remove\RemoveCapitalGoodRequest;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGood;
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

        foreach ($request->elements as $capitalGood) {
            if (!$capitalGood instanceof CapitalGood) {
                continue;
            }

            $commands[] = new RemoveCapitalGoodRequest(
                capitalGoodId: $capitalGood->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveCapitalGoodRequest);

        return new TranslatableMessage('Se ha eliminado el bien de capital {id}', ['{id}' => $command->capitalGoodId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveCapitalGoodRequest);

        return new TranslatableMessage('No se ha podido eliminar el bien de capital {id}', ['{id}' => $command->capitalGoodId]);
    }
}
