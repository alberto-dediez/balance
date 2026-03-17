<?php

namespace App\Property\Infrastructure\UI\Web\Admin\Brand\Bulk;

use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\Bulk\RemoveAction;
use Symfony\Component\Translation\TranslatableMessage;
use App\Property\Application\Brand\Remove\RemoveBrandRequest;

class RemoveBrand implements BulkAction
{
    use RemoveAction;

    public function commands(BulkRequest $request): array
    {
        if (count($request->elements) == 0) {
            return [];
        }

        $commands = [];

        foreach ($request->elements as $brand) {
            if (null === $brand || null === $brand->id()) {
                continue;
            }

            $commands[] = new RemoveBrandRequest(
                brandId: $brand->id()
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        return new TranslatableMessage("Se ha eliminado la marca {id}", ['{id}' => $command->brandId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        return new TranslatableMessage("No se ha podido eliminar la marca {id}", ['{id}' => $command->brandId]);
    }
}
