<?php

declare(strict_types=1);

namespace App\Bioscore\Application\BioscoreCatalogItem\UpdateMapping;

use App\Bioscore\Application\BioscoreCatalogItem\BioscoreCatalogItemAuthorizer;
use App\Bioscore\Application\BioscoreCatalogItem\Ignore\IgnoreBioscoreCatalogItemRequest;
use App\Bioscore\Application\BioscoreCatalogItem\Ignore\IgnoreBioscoreCatalogItemService;
use App\Bioscore\Application\BioscoreCatalogItem\Map\MapBioscoreCatalogItemRequest;
use App\Bioscore\Application\BioscoreCatalogItem\Map\MapBioscoreCatalogItemService;
use App\Bioscore\Application\BioscoreCatalogItem\Unmap\UnmapBioscoreCatalogItemRequest;
use App\Bioscore\Application\BioscoreCatalogItem\Unmap\UnmapBioscoreCatalogItemService;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItem;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemNotFoundException;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemRepository;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreMappingStatus;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

final class UpdateBioscoreCatalogItemMappingService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private BioscoreCatalogItemRepository $repository,
        private MapBioscoreCatalogItemService $mapItem,
        private UnmapBioscoreCatalogItemService $unmapItem,
        private IgnoreBioscoreCatalogItemService $ignoreItem,
        private BioscoreCatalogItemAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateBioscoreCatalogItemMappingRequest $request, User $user): UpdateBioscoreCatalogItemMappingResponse
    {
        $item = $this->repository->find($request->itemId);

        if (null === $item) {
            throw new BioscoreCatalogItemNotFoundException($request->itemId);
        }

        $this->authorizer->denyUnlessCanDoThat($item, SecurityAction::Edit, $user);

        if ($request->ignore) {
            $this->ignore($item, $user);
        } elseif (null !== $request->targetId && '' !== $request->targetId) {
            $this->map($item, $request->targetId, $user);
        } else {
            $this->unmap($item, $user);
        }

        return new UpdateBioscoreCatalogItemMappingResponse(item: $item);
    }

    private function ignore(BioscoreCatalogItem $item, User $user): void
    {
        if ($item->isIgnored()) {
            return;
        }

        $this->ignoreItem->__invoke(new IgnoreBioscoreCatalogItemRequest(itemId: $item->id()), $user);
    }

    private function map(BioscoreCatalogItem $item, string $targetId, User $user): void
    {
        if ($item->isMapped() && $item->targetId() === $targetId && BioscoreMappingStatus::ManuallyMapped === $item->status()) {
            return;
        }

        $this->mapItem->__invoke(new MapBioscoreCatalogItemRequest(itemId: $item->id(), targetId: $targetId), $user);
    }

    private function unmap(BioscoreCatalogItem $item, User $user): void
    {
        if (!$item->isMapped() && !$item->isIgnored()) {
            return;
        }

        $this->unmapItem->__invoke(new UnmapBioscoreCatalogItemRequest(itemId: $item->id()), $user);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateBioscoreCatalogItemMappingRequest;
    }
}
