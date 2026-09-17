<?php

declare(strict_types=1);

namespace App\Bioscore\Application\BioscoreCatalogItem\Register;

use App\Bioscore\Application\BioscoreCatalogItem\BioscoreCatalogItemAuthorizer;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItem;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemId;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterBioscoreCatalogItemService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private BioscoreCatalogItemRepository $repository,
        private BioscoreCatalogItemAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterBioscoreCatalogItemRequest $request, User $user): RegisterBioscoreCatalogItemResponse
    {
        $id = BioscoreCatalogItemId::generate();

        $this->authorizer->denyUnlessCanDoThat(new BioscoreCatalogItem(id: $id), SecurityAction::Register, $user);

        if (null !== $this->repository->findByCatalogAndBioscoreId($request->catalog, $request->bioscoreId)) {
            throw new \InvalidArgumentException('Bioscore catalog item already registered');
        }

        $item = new BioscoreCatalogItem(id: $id);
        $item->register($request->catalog, $request->bioscoreId, $request->name, $request->unit, $request->seenAt);

        $this->repository->add($item);

        return new RegisterBioscoreCatalogItemResponse(item: $item);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterBioscoreCatalogItemRequest;
    }
}
