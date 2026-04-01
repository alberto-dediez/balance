<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\CriticalPoint\Bulk;

use App\FoodLoss\Application\CriticalPoint\Remove\RemoveCriticalPointRequest;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPoint;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use Symfony\Component\Translation\TranslatableMessage;

class RemoveAction implements BulkAction
{
    public function id(): string
    {
        return 'remove';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Eliminar');
    }

    public function commands(BulkRequest $request): array
    {
        if (0 == count($request->elements)) {
            return [];
        }

        $commands = [];

        foreach ($request->elements as $criticalPoint) {
            if (!$criticalPoint instanceof CriticalPoint) {
                continue;
            }

            $commands[] = new RemoveCriticalPointRequest(
                criticalPointId: $criticalPoint->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveCriticalPointRequest);

        return new TranslatableMessage('Se ha eliminado el punto crítico {id}', ['{id}' => (string) $command->criticalPointId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveCriticalPointRequest);

        return new TranslatableMessage('No se ha podido eliminar el punto crítico {id}', ['{id}' => (string) $command->criticalPointId]);
    }
}
