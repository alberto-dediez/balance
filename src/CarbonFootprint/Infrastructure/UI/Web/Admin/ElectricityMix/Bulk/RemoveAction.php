<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\ElectricityMix\Bulk;

use App\CarbonFootprint\Application\ElectricityMix\Remove\RemoveElectricityMixRequest;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMix;
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

        foreach ($request->elements as $electricityMix) {
            if (!$electricityMix instanceof ElectricityMix) {
                continue;
            }

            $commands[] = new RemoveElectricityMixRequest(
                electricityMixId: $electricityMix->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveElectricityMixRequest);

        return new TranslatableMessage('Se ha eliminado el mix eléctrico {id}', ['{id}' => $command->electricityMixId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveElectricityMixRequest);

        return new TranslatableMessage('No se ha podido eliminar el mix eléctrico {id}', ['{id}' => $command->electricityMixId]);
    }
}
