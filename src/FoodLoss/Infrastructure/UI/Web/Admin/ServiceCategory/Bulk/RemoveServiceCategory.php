<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Admin\ServiceCategory\Bulk;

use App\FoodLoss\Application\ServiceCategory\Remove\RemoveServiceCategoryRequest;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use Symfony\Component\Translation\TranslatableMessage;

class RemoveServiceCategory implements BulkAction
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
        if (0 === count($request->elements)) {
            return [];
        }

        $commands = [];

        foreach ($request->elements as $serviceCategory) {
            if (null === $serviceCategory) {
                continue;
            }
            if (null === $serviceCategory->id()) {
                continue;
            }
            $commands[] = new RemoveServiceCategoryRequest(
                serviceCategoryId: $serviceCategory->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveServiceCategoryRequest);

        return new TranslatableMessage('Se ha eliminado la categoría de servicios {id}', ['{id}' => $command->serviceCategoryId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveServiceCategoryRequest);

        return new TranslatableMessage('No se ha podido eliminar la categoría de servicios {id}', ['{id}' => $command->serviceCategoryId]);
    }
}
