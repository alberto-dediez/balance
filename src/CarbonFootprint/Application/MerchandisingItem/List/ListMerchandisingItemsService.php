<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MerchandisingItem\List;

use App\CarbonFootprint\Application\MerchandisingItem\MerchandisingItemAuthorizer;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItem;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemId;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListMerchandisingItemsService
{
    public function __construct(private MerchandisingItemRepository $repository, private MerchandisingItemAuthorizer $authorizer) {}

    public function __invoke(ListMerchandisingItemsRequest $request, User $user): ListMerchandisingItemsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new MerchandisingItem(id: MerchandisingItemId::generate()), SecurityAction::List, $user);

        $merchandisingItems = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListMerchandisingItemsResponse($merchandisingItems, $totalResults);
    }
}
