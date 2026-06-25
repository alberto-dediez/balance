<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MerchandisingItem\Remove;

use App\CarbonFootprint\Application\MerchandisingItem\MerchandisingItemAuthorizer;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveMerchandisingItemService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private MerchandisingItemRepository $repository, private MerchandisingItemAuthorizer $authorizer) {}

    public function __invoke(RemoveMerchandisingItemRequest $request, User $user): RemoveMerchandisingItemResponse
    {
        $merchandisingItem = $this->repository->find($request->merchandisingItemId);

        if (null == $merchandisingItem) {
            throw new \InvalidArgumentException('MerchandisingItem not found');
        }

        $this->authorizer->denyUnlessCanDoThat($merchandisingItem, SecurityAction::Remove, $user);

        $merchandisingItem->remove();

        $this->repository->add($merchandisingItem);

        return new RemoveMerchandisingItemResponse(
            merchandisingItemId: $merchandisingItem->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveMerchandisingItemRequest;
    }
}
