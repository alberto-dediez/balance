<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\EconomicSector\Bulk;

use App\CarbonFootprint\Application\EconomicSector\Remove\RemoveEconomicSectorRequest;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSector;
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

        foreach ($request->elements as $economicSector) {
            if (!$economicSector instanceof EconomicSector) {
                continue;
            }

            $commands[] = new RemoveEconomicSectorRequest(
                economicSectorId: $economicSector->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveEconomicSectorRequest);

        return new TranslatableMessage('Se ha eliminado el sector económico {id}', ['{id}' => $command->economicSectorId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveEconomicSectorRequest);

        return new TranslatableMessage('No se ha podido eliminar el sector económico {id}', ['{id}' => $command->economicSectorId]);
    }
}
