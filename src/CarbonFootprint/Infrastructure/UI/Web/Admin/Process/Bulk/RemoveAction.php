<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\Process\Bulk;

use App\CarbonFootprint\Application\Process\Remove\RemoveProcessRequest;
use App\CarbonFootprint\Domain\Model\Process\Process;
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

        foreach ($request->elements as $process) {
            if (!$process instanceof Process) {
                continue;
            }

            $commands[] = new RemoveProcessRequest(
                processId: $process->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveProcessRequest);

        return new TranslatableMessage('Se ha eliminado el proceso {id}', ['{id}' => $command->processId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveProcessRequest);

        return new TranslatableMessage('No se ha podido eliminar el proceso {id}', ['{id}' => $command->processId]);
    }
}
