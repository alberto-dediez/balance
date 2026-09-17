<?php

declare(strict_types=1);

namespace App\Bioscore\Application\BioscoreCatalogItem\Unmap;

use App\Bioscore\Application\BioscoreCatalogItem\BioscoreCatalogItemAuthorizer;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemNotFoundException;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UnmapBioscoreCatalogItemService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private BioscoreCatalogItemRepository $repository,
        private BioscoreCatalogItemAuthorizer $authorizer,
    ) {}

    public function __invoke(UnmapBioscoreCatalogItemRequest $request, User $user): UnmapBioscoreCatalogItemResponse
    {
        $item = $this->repository->find($request->itemId);

        if (null === $item) {
            throw new BioscoreCatalogItemNotFoundException($request->itemId);
        }

        $this->authorizer->denyUnlessCanDoThat($item, SecurityAction::Edit, $user);

        $item->unmap();

        $this->repository->add($item);

        return new UnmapBioscoreCatalogItemResponse(item: $item);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UnmapBioscoreCatalogItemRequest;
    }
}
