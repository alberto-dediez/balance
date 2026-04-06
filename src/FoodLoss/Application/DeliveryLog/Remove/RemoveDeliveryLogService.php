<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DeliveryLog\Remove;

use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLog;
use App\FoodLoss\Application\DeliveryLog\DeliveryLogAuthorizer;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveDeliveryLogService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private DeliveryLogRepository $repository,
        private DeliveryLogAuthorizer $authorizer,
    ) {}

    public function __invoke(RemoveDeliveryLogRequest $request, User $user): RemoveDeliveryLogResponse
    {
        $deliveryLog = $this->repository->find($request->deliveryLogId);

        if (!$deliveryLog instanceof DeliveryLog) {
            throw new \InvalidArgumentException('DeliveryLog not found');
        }

        $this->authorizer->denyUnlessCanDoThat($deliveryLog, SecurityAction::Remove, $user);

        $deliveryLog->remove();

        $this->repository->add($deliveryLog);

        return new RemoveDeliveryLogResponse(
            deliveryLogId: $deliveryLog->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveDeliveryLogRequest;
    }
}
