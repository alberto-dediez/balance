<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DeliveryLog\Update;

use App\FoodLoss\Application\DeliveryLog\DeliveryLogAuthorizer;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UpdateDeliveryLogService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private DeliveryLogRepository $repository,
        private DeliveryLogAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateDeliveryLogRequest $request, User $user): UpdateDeliveryLogResponse
    {
        if (null === $request->recipientOrganizationId) {
            throw new \InvalidArgumentException('RecipientOrganization is required');
        }

        if (null === $request->propertyId) {
            throw new \InvalidArgumentException('Property is required');
        }

        $deliveryLog = $this->repository->find($request->deliveryLogId);

        if (null === $deliveryLog) {
            throw new \InvalidArgumentException('DeliveryLog not found');
        }

        $this->authorizer->denyUnlessCanDoThat($deliveryLog, SecurityAction::Edit, $user);

        $deliveryLog->update(
            date: $request->date,
            quantity: $request->quantity,
            unit: $request->unit,
            productType: $request->productType,
            batch: $request->batch,
            recipientOrganizationId: $request->recipientOrganizationId,
            propertyId: $request->propertyId,
        );

        $this->repository->add($deliveryLog);

        return new UpdateDeliveryLogResponse(
            deliveryLogId: $deliveryLog->id(),
            productType: $deliveryLog->productType(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateDeliveryLogRequest;
    }
}
