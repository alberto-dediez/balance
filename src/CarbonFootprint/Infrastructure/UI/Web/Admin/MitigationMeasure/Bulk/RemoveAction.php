<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\MitigationMeasure\Bulk;

use App\CarbonFootprint\Application\MitigationMeasure\Remove\RemoveMitigationMeasureRequest;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasure;
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

        foreach ($request->elements as $mitigationMeasure) {
            if (!$mitigationMeasure instanceof MitigationMeasure) {
                continue;
            }

            $commands[] = new RemoveMitigationMeasureRequest(
                mitigationMeasureId: $mitigationMeasure->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveMitigationMeasureRequest);

        return new TranslatableMessage('Se ha eliminado la medida {id}', ['{id}' => (string) $command->mitigationMeasureId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveMitigationMeasureRequest);

        return new TranslatableMessage('No se ha podido eliminar la medida {id}', ['{id}' => (string) $command->mitigationMeasureId]);
    }
}
