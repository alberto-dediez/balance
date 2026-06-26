<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CateringItem\Remove;

use App\CarbonFootprint\Application\CateringItem\CateringItemAuthorizer;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveCateringItemService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private CateringItemRepository $repository, private CateringItemAuthorizer $authorizer) {}

    public function __invoke(RemoveCateringItemRequest $request, User $user): RemoveCateringItemResponse
    {
        $cateringItem = $this->repository->find($request->cateringItemId);

        if (null == $cateringItem) {
            throw new \InvalidArgumentException('CateringItem not found');
        }

        $this->authorizer->denyUnlessCanDoThat($cateringItem, SecurityAction::Remove, $user);

        $cateringItem->remove();

        $this->repository->add($cateringItem);

        return new RemoveCateringItemResponse(
            cateringItemId: $cateringItem->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveCateringItemRequest;
    }
}
