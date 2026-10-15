<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\PurchaseService\Bulk;

use App\CarbonFootprint\Application\PurchaseService\Remove\RemovePurchaseServiceRequest;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseService;
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

        foreach ($request->elements as $purchaseService) {
            if (!$purchaseService instanceof PurchaseService) {
                continue;
            }

            $commands[] = new RemovePurchaseServiceRequest(
                purchaseServiceId: $purchaseService->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemovePurchaseServiceRequest);

        return new TranslatableMessage('Se ha eliminado el bien o servicio comprado {id}', ['{id}' => $command->purchaseServiceId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemovePurchaseServiceRequest);

        return new TranslatableMessage('No se ha podido eliminar el bien o servicio comprado {id}', ['{id}' => $command->purchaseServiceId]);
    }
}
