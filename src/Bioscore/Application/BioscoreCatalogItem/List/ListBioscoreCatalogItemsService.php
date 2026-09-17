<?php

declare(strict_types=1);

namespace App\Bioscore\Application\BioscoreCatalogItem\List;

use App\Bioscore\Application\BioscoreCatalogItem\BioscoreCatalogItemAuthorizer;
use App\Bioscore\Application\BioscoreCatalogItem\CatalogTargetFinder;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItem;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemId;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListBioscoreCatalogItemsService
{
    public function __construct(
        private BioscoreCatalogItemRepository $repository,
        private CatalogTargetFinder $targetFinder,
        private BioscoreCatalogItemAuthorizer $authorizer,
    ) {}

    public function __invoke(ListBioscoreCatalogItemsRequest $request, User $user): ListBioscoreCatalogItemsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(
            new BioscoreCatalogItem(id: BioscoreCatalogItemId::generate()),
            SecurityAction::List,
            $user,
        );

        if (null === $request->catalog) {
            throw new \InvalidArgumentException('A Bioscore catalog is required');
        }

        $targetsById = [];

        foreach ($this->targetFinder->all($request->catalog) as $target) {
            $targetsById[$target->id] = $target;
        }

        return new ListBioscoreCatalogItemsResponse(
            items: $this->repository->findByFilter($request),
            totalResults: $this->repository->totalResultsByFilter($request),
            pendingCount: $this->repository->countPending($request->catalog),
            targetsById: $targetsById,
        );
    }
}
