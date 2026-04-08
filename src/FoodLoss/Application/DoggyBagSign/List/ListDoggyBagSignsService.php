<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DoggyBagSign\List;

use App\FoodLoss\Application\DoggyBagSign\DoggyBagSignAuthorizer;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSign;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignId;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class ListDoggyBagSignsService
{
    public function __construct(private DoggyBagSignRepository $repository, private DoggyBagSignAuthorizer $authorizer) {}

    public function __invoke(ListDoggyBagSignsRequest $request, User $user): ListDoggyBagSignsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(
            new DoggyBagSign(id: DoggyBagSignId::generate(), accountId: $request->accountId),
            SecurityAction::List,
            $user,
        );

        $doggyBagSigns = $this->repository->findByFilter($request);

        $totalResults = $this->repository->totalResultsByFilter($request);

        return new ListDoggyBagSignsResponse($doggyBagSigns, $totalResults);
    }
}
