<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\EmissionFactorGroup\Bulk;

use App\CarbonFootprint\Application\EmissionFactorGroup\Remove\RemoveEmissionFactorGroupRequest;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
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

        foreach ($request->elements as $emissionFactorGroup) {
            if (!$emissionFactorGroup instanceof EmissionFactorGroup) {
                continue;
            }

            $commands[] = new RemoveEmissionFactorGroupRequest(
                emissionFactorGroupId: $emissionFactorGroup->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveEmissionFactorGroupRequest);

        return new TranslatableMessage('Se ha eliminado el grupo de coeficientes {id}', ['{id}' => $command->emissionFactorGroupId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveEmissionFactorGroupRequest);

        return new TranslatableMessage('No se ha podido eliminar el grupo de coeficientes {id}', ['{id}' => $command->emissionFactorGroupId]);
    }
}
