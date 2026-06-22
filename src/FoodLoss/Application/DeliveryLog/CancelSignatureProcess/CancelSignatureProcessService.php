<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DeliveryLog\CancelSignatureProcess;

use App\FoodLoss\Application\DeliveryLog\DeliveryLogAuthorizer;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class CancelSignatureProcessService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private DeliveryLogRepository $repository,
        private DeliveryLogAuthorizer $authorizer,
    ) {}

    public function __invoke(CancelSignatureProcessRequest $request, User $user): CancelSignatureProcessResponse
    {
        $deliveryLogs = $this->repository->findBySignatureRequestId($request->signatureRequestId);

        foreach ($deliveryLogs as $deliveryLog) {
            $this->authorizer->denyUnlessCanDoThat($deliveryLog, SecurityAction::View, $user);
        }

        foreach ($deliveryLogs as $deliveryLog) {
            $deliveryLog->cancelSignatureProcess();
            $this->repository->add($deliveryLog);
        }

        return new CancelSignatureProcessResponse(cancelledCount: count($deliveryLogs));
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof CancelSignatureProcessRequest;
    }
}
