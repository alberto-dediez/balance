<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\Admin\Project\Bulk;

use App\Balance\Application\Project\Remove\RemoveProjectRequest;
use App\Balance\Domain\Model\Project\Project;
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

        foreach ($request->elements as $project) {
            if (!$project instanceof Project) {
                continue;
            }

            $commands[] = new RemoveProjectRequest(
                projectId: $project->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveProjectRequest);

        return new TranslatableMessage('Se ha eliminado el proyecto {id}', ['{id}' => $command->projectId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveProjectRequest);

        return new TranslatableMessage('No se ha podido eliminar el proyecto {id}', ['{id}' => $command->projectId]);
    }
}
