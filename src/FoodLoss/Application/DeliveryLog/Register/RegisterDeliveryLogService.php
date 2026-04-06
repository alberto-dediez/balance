<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DeliveryLog\Register;

use App\FoodLoss\Application\DeliveryLog\DeliveryLogAuthorizer;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLog;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogId;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterDeliveryLogService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private DeliveryLogRepository $repository,
        private DeliveryLogAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterDeliveryLogRequest $request, User $user): RegisterDeliveryLogResponse
    {
        if (null === $request->recipientOrganizationId) {
            throw new \InvalidArgumentException('RecipientOrganization is required');
        }

        if (null === $request->propertyId) {
            throw new \InvalidArgumentException('Property is required');
        }

        if (null === $request->accountId) {
            throw new \InvalidArgumentException('AccountId is required');
        }

        $id = DeliveryLogId::generate();

        $deliveryLog = new DeliveryLog(id: $id, accountId: $request->accountId);

        $this->authorizer->denyUnlessCanDoThat($deliveryLog, SecurityAction::List, $user);

        $deliveryLog->register(
            date: $request->date ?? new \DateTimeImmutable('NOW'),
            quantity: $request->quantity,
            unit: $request->unit,
            productType: $request->productType,
            batch: $request->batch,
            recipientOrganizationId: $request->recipientOrganizationId,
            accountId: $request->accountId,
            propertyId: $request->propertyId,
        );

        $this->repository->add($deliveryLog);

        return new RegisterDeliveryLogResponse(
            deliveryLogId: $deliveryLog->id(),
            productType: $deliveryLog->productType(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterDeliveryLogRequest;
    }
}
