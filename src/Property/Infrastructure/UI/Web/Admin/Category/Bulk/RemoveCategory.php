<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Web\Admin\Category\Bulk;

use App\Property\Application\Category\Remove\RemoveCategoryRequest;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\Bulk\RemoveAction;
use Symfony\Component\Translation\TranslatableMessage;

class RemoveCategory implements BulkAction
{
    use RemoveAction;

    public function commands(BulkRequest $request): array
    {
        if (0 === count($request->elements)) {
            return [];
        }

        $commands = [];

        foreach ($request->elements as $category) {
            if (null === $category) {
                continue;
            }
            if (null === $category->id()) {
                continue;
            }
            $commands[] = new RemoveCategoryRequest(
                categoryId: $category->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        /* @var \App\Property\Application\Category\Remove\RemoveCategoryRequest $command */
        return new TranslatableMessage('Se ha eliminado la categoría {id}', ['{id}' => $command->categoryId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        /* @var \App\Property\Application\Category\Remove\RemoveCategoryRequest $command */
        return new TranslatableMessage('No se ha podido eliminar la categoría {id}', ['{id}' => $command->categoryId]);
    }
}
