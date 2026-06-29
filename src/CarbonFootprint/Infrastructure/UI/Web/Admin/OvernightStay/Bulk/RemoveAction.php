<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\OvernightStay\Bulk;

use App\CarbonFootprint\Application\OvernightStay\Remove\RemoveOvernightStayRequest;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStay;
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

        foreach ($request->elements as $overnightStay) {
            if (!$overnightStay instanceof OvernightStay) {
                continue;
            }

            $commands[] = new RemoveOvernightStayRequest(
                overnightStayId: $overnightStay->id(),
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveOvernightStayRequest);

        return new TranslatableMessage('Se ha eliminado la pernoctación {id}', ['{id}' => $command->overnightStayId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        assert($command instanceof RemoveOvernightStayRequest);

        return new TranslatableMessage('No se ha podido eliminar la pernoctación {id}', ['{id}' => $command->overnightStayId]);
    }
}
