<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\MitigationMeasureCategory\Bulk;

use App\CarbonFootprint\Application\MitigationMeasureCategory\Remove\RemoveMitigationMeasureCategoryRequest;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategory;
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

        foreach ($request->elements as $mitigationMeasureCategory) {
            if (!$mitigationMeasureCategory instanceof MitigationMeasureCategory) {
                continue;
            }

            $commands[] = new RemoveMitigationMeasureCategoryRequest(
                mitigationMeasureCategoryId: $mitigationMeasureCategory->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveMitigationMeasureCategoryRequest);

        return new TranslatableMessage('Se ha eliminado la categoría de medidas {id}', ['{id}' => (string) $command->mitigationMeasureCategoryId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveMitigationMeasureCategoryRequest);

        return new TranslatableMessage('No se ha podido eliminar la categoría de medidas {id}', ['{id}' => (string) $command->mitigationMeasureCategoryId]);
    }
}
