<?php

declare(strict_types=1);

namespace App\Bioscore\Application\BioscoreCatalogItem\Map;

use App\Bioscore\Application\BioscoreCatalogItem\BioscoreCatalogItemAuthorizer;
use App\Bioscore\Application\BioscoreCatalogItem\CatalogTargetFinder;
use App\Bioscore\Application\BioscoreCatalogItem\CatalogTargetNotFoundException;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemNotFoundException;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class MapBioscoreCatalogItemService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private BioscoreCatalogItemRepository $repository,
        private CatalogTargetFinder $targetFinder,
        private BioscoreCatalogItemAuthorizer $authorizer,
    ) {}

    public function __invoke(MapBioscoreCatalogItemRequest $request, User $user): MapBioscoreCatalogItemResponse
    {
        $item = $this->repository->find($request->itemId);

        if (null === $item) {
            throw new BioscoreCatalogItemNotFoundException($request->itemId);
        }

        $this->authorizer->denyUnlessCanDoThat($item, SecurityAction::Edit, $user);

        if (null === $this->targetFinder->find($item->catalog(), $request->targetId)) {
            throw new CatalogTargetNotFoundException($item->catalog(), $request->targetId);
        }

        $item->map($request->targetId, automatic: false);

        $this->repository->add($item);

        return new MapBioscoreCatalogItemResponse(item: $item);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof MapBioscoreCatalogItemRequest;
    }
}
