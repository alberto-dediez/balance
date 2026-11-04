<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Web\Admin\DistributorAssignment\Bulk;

use App\Property\Application\DistributorAssignment\Remove\RemoveDistributorAssignmentRequest;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\Bulk\RemoveAction;
use Symfony\Component\Translation\TranslatableMessage;

class RemoveDistributorAssignment implements BulkAction
{
    use RemoveAction;

    public function commands(BulkRequest $request): array
    {
        if (0 === count($request->elements)) {
            return [];
        }

        $commands = [];

        foreach ($request->elements as $assignment) {
            if (null === $assignment) {
                continue;
            }

            $commands[] = new RemoveDistributorAssignmentRequest($assignment->id());
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveDistributorAssignmentRequest);

        return new TranslatableMessage('Se ha eliminado la asignación de distribuidor {id}', ['{id}' => $command->id]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveDistributorAssignmentRequest);

        return new TranslatableMessage('No se ha podido eliminar la asignación de distribuidor {id}', ['{id}' => $command->id]);
    }
}
