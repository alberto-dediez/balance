<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Web\Admin\Brand\Bulk;

use App\Property\Application\Brand\Remove\RemoveBrandRequest;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\Bulk\RemoveAction;
use Symfony\Component\Translation\TranslatableMessage;

class RemoveBrand implements BulkAction
{
    use RemoveAction;

    public function commands(BulkRequest $request): array
    {
        if (0 === count($request->elements)) {
            return [];
        }

        $commands = [];

        foreach ($request->elements as $brand) {
            if (null === $brand) {
                continue;
            }
            if (null === $brand->id()) {
                continue;
            }
            $commands[] = new RemoveBrandRequest(
                brandId: $brand->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveBrandRequest);

        return new TranslatableMessage('Se ha eliminado la marca {id}', ['{id}' => $command->brandId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveBrandRequest);

        return new TranslatableMessage('No se ha podido eliminar la marca {id}', ['{id}' => $command->brandId]);
    }
}
