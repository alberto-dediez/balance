<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\UI\Web\Admin\MaterialityTopic\Bulk;

use App\Materiality\Application\MaterialityTopic\Remove\RemoveMaterialityTopicRequest;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopic;
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

        foreach ($request->elements as $materialityTopic) {
            if (!$materialityTopic instanceof MaterialityTopic) {
                continue;
            }

            $commands[] = new RemoveMaterialityTopicRequest(
                materialityTopicId: $materialityTopic->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveMaterialityTopicRequest);

        return new TranslatableMessage('Se ha eliminado la categoría de materialidad {id}', ['{id}' => $command->materialityTopicId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveMaterialityTopicRequest);

        return new TranslatableMessage('No se ha podido eliminar la categoría de materialidad {id}', ['{id}' => $command->materialityTopicId]);
    }
}
