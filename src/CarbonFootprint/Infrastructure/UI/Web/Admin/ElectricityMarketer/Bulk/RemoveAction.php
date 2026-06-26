<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\ElectricityMarketer\Bulk;

use App\CarbonFootprint\Application\ElectricityMarketer\Remove\RemoveElectricityMarketerRequest;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketer;
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

        foreach ($request->elements as $electricityMarketer) {
            if (!$electricityMarketer instanceof ElectricityMarketer) {
                continue;
            }

            $commands[] = new RemoveElectricityMarketerRequest(
                electricityMarketerId: $electricityMarketer->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveElectricityMarketerRequest);

        return new TranslatableMessage('Se ha eliminado la comercializadora eléctrica {id}', ['{id}' => $command->electricityMarketerId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveElectricityMarketerRequest);

        return new TranslatableMessage('No se ha podido eliminar la comercializadora eléctrica {id}', ['{id}' => $command->electricityMarketerId]);
    }
}
