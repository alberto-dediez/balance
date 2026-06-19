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

class DeliverWithSignatureBulkAction implements BulkAction
{
    public function id(): string
    {
        return 'deliver-with-signature';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Solicitar confirmación de entrega');
    }

    public function commands(BulkRequest $request): array
    {
        if (!$request instanceof BulkDeliveryLogRequest || 0 === count($request->elements)) {
            return [];
        }

        $grouped = [];
        foreach ($request->elements as $element) {
            if (!$element instanceof DeliveryLog) {
                continue;
            }

            $orgId = (string) $element->recipientOrganizationId();
            $grouped[$orgId][] = $element->id();
        }

        $commands = [];
        foreach ($grouped as $deliveryLogIds) {
            $command = new DeliverDeliveryLogsRequest();
            $command->deliveryLogIds = $deliveryLogIds;
            $command->withSignature = true;
            $command->deliveredAt = $request->deliveredAt;
            $command->pickupType = $request->pickupType ?? PickupType::Pickup;
            $command->otpRequired = $request->otpRequired;

            $commands[] = $command;
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        return new TranslatableMessage('Se ha solicitado la confirmación de entrega a la persona designada.');
    }

    public function failure(Command $command): TranslatableMessage
    {
        return new TranslatableMessage('No se ha podido solicitar la confirmación de entrega, verifica que los datos insertados son correctos.');
    }
}
