<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CateringItem\List;

use App\CarbonFootprint\Application\CateringItem\CateringItemAuthorizer;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItem;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemId;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListCateringItemsService
{
    public function __construct(private CateringItemRepository $repository, private CateringItemAuthorizer $authorizer) {}

    public function __invoke(ListCateringItemsRequest $request, User $user): ListCateringItemsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new CateringItem(id: CateringItemId::generate()), SecurityAction::List, $user);

        $cateringItems = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListCateringItemsResponse($cateringItems, $totalResults);
    }
}
