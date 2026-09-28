<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\UI\Web\Admin\MaterialityQuestion\Bulk;

use App\Materiality\Application\MaterialityQuestion\Remove\RemoveMaterialityQuestionRequest;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestion;
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

        foreach ($request->elements as $materialityQuestion) {
            if (!$materialityQuestion instanceof MaterialityQuestion) {
                continue;
            }

            $commands[] = new RemoveMaterialityQuestionRequest(
                materialityQuestionId: $materialityQuestion->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveMaterialityQuestionRequest);

        return new TranslatableMessage('Se ha eliminado la pregunta de materialidad {id}', ['{id}' => $command->materialityQuestionId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveMaterialityQuestionRequest);

        return new TranslatableMessage('No se ha podido eliminar la pregunta de materialidad {id}', ['{id}' => $command->materialityQuestionId]);
    }
}
