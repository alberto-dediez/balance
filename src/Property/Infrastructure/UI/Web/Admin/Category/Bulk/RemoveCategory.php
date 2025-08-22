<?php

namespace App\Property\Infrastructure\UI\Web\Admin\Category\Bulk;

use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use Symfony\Component\Translation\TranslatableMessage;
use App\Property\Application\Category\Remove\RemoveCategoryRequest;

class RemoveCategory implements BulkAction
{
    public function id(): string
    {
        return 'remove';
    }

    public function name(): string
    {
        return new TranslatableMessage('Eliminar');
    }

    public function commands(BulkRequest $request): array
    {
        if (count($request->elements) == 0) {
            return [];
        }

        $commands = [];

        foreach ($request->elements as $category) {
            if (null === $category || null === $category->id()) {
                continue;
            }

            $commands[] = new RemoveCategoryRequest(
                categoryId: $category->id()
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        return new TranslatableMessage("Se ha eliminado la categoría {id}", ['{id}' => $command->categoryId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        return new TranslatableMessage("No se ha podido eliminar la categoría {id}", ['{id}' => $command->categoryId]);
    }
}