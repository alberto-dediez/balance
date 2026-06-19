<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\DeliveryLog\Bulk;

use App\FoodLoss\Application\DeliveryLog\Deliver\DeliverDeliveryLogsRequest;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLog;
use App\FoodLoss\Domain\Model\RecipientOrganization\PickupType;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use Symfony\Component\Translation\TranslatableMessage;

class DeliverBulkAction implements BulkAction
{
    public function id(): string
    {
        return 'deliver';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Marcar como entregado');
    }

    public function commands(BulkRequest $request): array
    {
        if (!$request instanceof BulkDeliveryLogRequest || 0 === count($request->elements)) {
            return [];
        }

        $command = new DeliverDeliveryLogsRequest();
        $command->deliveryLogIds = array_values(array_filter(
            array_map(fn(mixed $log) => $log instanceof DeliveryLog ? $log->id() : null, $request->elements),
        ));
        $command->deliveredAt = $request->deliveredAt;
        $command->pickupType = $request->pickupType ?? PickupType::Pickup;

        return [$command];
    }

    public function success(Command $command): TranslatableMessage
    {
        return new TranslatableMessage('Las entregas seleccionadas han sido marcadas como entregadas');
    }

    public function failure(Command $command): TranslatableMessage
    {
        return new TranslatableMessage('No se han podido marcar las entregas como entregadas');
    }
}
