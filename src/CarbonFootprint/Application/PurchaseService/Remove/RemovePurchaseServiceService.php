<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PurchaseService\Remove;

use App\CarbonFootprint\Application\PurchaseService\PurchaseServiceAuthorizer;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemovePurchaseServiceService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private PurchaseServiceRepository $repository, private PurchaseServiceAuthorizer $authorizer) {}

    public function __invoke(RemovePurchaseServiceRequest $request, User $user): RemovePurchaseServiceResponse
    {
        $purchaseService = $this->repository->find($request->purchaseServiceId);

        if (null == $purchaseService) {
            throw new \InvalidArgumentException('PurchaseService not found');
        }

        $this->authorizer->denyUnlessCanDoThat($purchaseService, SecurityAction::Remove, $user);

        $purchaseService->remove();

        $this->repository->add($purchaseService);

        return new RemovePurchaseServiceResponse(
            purchaseServiceId: $purchaseService->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemovePurchaseServiceRequest;
    }
}
