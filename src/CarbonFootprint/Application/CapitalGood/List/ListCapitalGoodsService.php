<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CapitalGood\List;

use App\CarbonFootprint\Application\CapitalGood\CapitalGoodAuthorizer;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGood;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodId;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListCapitalGoodsService
{
    public function __construct(private CapitalGoodRepository $repository, private CapitalGoodAuthorizer $authorizer) {}

    public function __invoke(ListCapitalGoodsRequest $request, User $user): ListCapitalGoodsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new CapitalGood(id: CapitalGoodId::generate()), SecurityAction::List, $user);

        $capitalGoods = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListCapitalGoodsResponse($capitalGoods, $totalResults);
    }
}
